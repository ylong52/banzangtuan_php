<template>
  <div class="max-w-4xl mx-auto p-6">
    <!-- 用户列表组件示例 -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">用户管理</h2>
        <button 
          @click="handleCreate"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg 
                 hover:bg-blue-700 transition"
        >
          添加用户
        </button>
      </div>

      <!-- 加载状态 -->
      <div v-if="loading">
        <Loading :loading="loading" />
      </div>

      <!-- 错误提示 -->
      <div v-else-if="error">
        <ErrorMessage :error="error" />
      </div>

      <!-- 用户列表 -->
      <div v-else class="space-y-4">
        <div 
          v-for="user in userList" 
          :key="user.id"
          class="flex items-center justify-between p-4 border 
                 border-gray-200 rounded-lg hover:bg-gray-50 transition"
        >
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full 
                        flex items-center justify-center">
              <span class="text-blue-600 font-semibold">
                {{ user.name[0] }}
              </span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-800">{{ user.name }}</h3>
              <p class="text-sm text-gray-500">{{ user.email }}</p>
            </div>
          </div>
          <div class="flex space-x-2">
            <button 
              @click="handleEdit(user)"
              class="px-3 py-1 text-sm bg-green-100 text-green-700 
                     rounded hover:bg-green-200 transition"
            >
              编辑
            </button>
            <button 
              @click="handleDelete(user.id)"
              class="px-3 py-1 text-sm bg-red-100 text-red-700 
                     rounded hover:bg-red-200 transition"
            >
              删除
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useApi } from '@/composables/useApi'
import { userApi } from '@/api/modules/user'
import Loading from '@/components/common/Loading.vue'
import ErrorMessage from '@/components/common/ErrorMessage.vue'

const { loading, error, data: userList, execute } = useApi(userApi.getList)

onMounted(async () => {
  await execute({ page: 1, limit: 10 })
})

const handleCreate = () => {
  // 打开创建对话框
  console.log('创建用户')
}

const handleEdit = (user) => {
  // 打开编辑对话框
  console.log('编辑用户', user)
}

const handleDelete = async (id) => {
  if (confirm('确定删除该用户吗?')) {
    await userApi.delete(id)
    await execute({ page: 1, limit: 10 }) // 刷新列表
  }
}
</script>

