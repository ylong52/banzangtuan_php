<template>
  <div v-if="error" 
       class="bg-red-50 border border-red-200 text-red-700 
              px-4 py-3 rounded">
    {{ error }}
  </div>
</template>

<script setup>
defineProps({
  error: {
    type: String,
    default: null
  }
})
</script>

