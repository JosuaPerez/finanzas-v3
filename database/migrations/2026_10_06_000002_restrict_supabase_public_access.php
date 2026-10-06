<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        $roles = DB::table('pg_roles')->whereIn('rolname', ['anon', 'authenticated'])->pluck('rolname')->all();
        if ($roles === []) {
            return; // Ordinary PostgreSQL installations have no Supabase API roles.
        }
        $currentRole = DB::selectOne('select current_user as name')->name;
        if (in_array($currentRole, $roles, true)) {
            throw new RuntimeException('Laravel requiere un rol de servidor; no uses anon ni authenticated para la conexión de base de datos.');
        }

        // These tables belong to the Laravel backend, not Supabase Auth users.
        // Keep the owner/server access; block direct public REST access.
        foreach (['users', 'expenses', 'budgets', 'debts', 'goals', 'sessions', 'password_reset_tokens',
            'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks', 'campaign_bosses', 'achievement_user', 'personal_access_tokens', 'financial_submissions'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $qualified = '"public"."'.$table.'"';
            $access = DB::selectOne('select pg_get_userbyid(c.relowner) as owner, r.rolsuper, r.rolbypassrls
                from pg_class c join pg_namespace n on n.oid = c.relnamespace
                join pg_roles r on r.rolname = current_user where n.nspname = ? and c.relname = ?', ['public', $table]);
            if ($access->owner !== $currentRole && ! $access->rolsuper && ! $access->rolbypassrls) {
                throw new RuntimeException('El rol de Laravel debe ser propietario de sus tablas o tener una política de servidor revisada antes de activar RLS.');
            }
            DB::statement('ALTER TABLE '.$qualified.' ENABLE ROW LEVEL SECURITY');
            DB::statement('REVOKE ALL PRIVILEGES ON TABLE '.$qualified.' FROM PUBLIC');
            foreach ($roles as $role) {
                DB::statement('REVOKE ALL PRIVILEGES ON TABLE '.$qualified.' FROM "'.$role.'"');
            }
        }
    }

    public function down(): void
    {
        // Never reopen public financial data as a side effect of a rollback.
        // Restoring grants/policies requires a deliberate database access review.
    }
};
