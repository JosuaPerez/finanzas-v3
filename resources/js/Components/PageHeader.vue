<script setup>
defineProps({
    subtitle:    { type: String, default: '' },
    title:       { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-8 px-4 sm:px-0">
        <p v-if="subtitle" class="text-xs font-bold uppercase tracking-widest text-blue-500 mb-1">
            {{ subtitle }}
        </p>
        <h1 class="text-3xl font-black text-white tracking-tight">{{ title }}</h1>
        <p v-if="description" class="text-slate-400 mt-1 text-sm">{{ description }}</p>
    </div>
</template>
