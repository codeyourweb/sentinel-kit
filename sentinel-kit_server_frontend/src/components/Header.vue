<template>
  <header class="bg-white shadow-md p-4 flex justify-between items-center sticky top-0 z-10">
    <div class="text-xl font-semibold text-gray-800">
      </div>

    <button 
      @click="logout"
      class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-150 ease-in-out flex items-center"
      title="Log out"
    >
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
      Log out
    </button>
  </header>
</template>

<script setup>
    import { useRouter } from 'vue-router';
    const router = useRouter();

const logout = () => {
    router.push({ name: 'Logout' });
};
</script>