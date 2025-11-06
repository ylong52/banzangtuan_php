<template>
  <div v-if="loading" class="flex justify-center items-center py-12">
    <div class="animate-spin rounded-full h-12 w-12 border-4 
                border-blue-500 border-t-transparent"></div>
  </div>
</template>

<script setup>
defineProps({
  loading: {
    type: Boolean,
    default: false
  }
})
</script>

