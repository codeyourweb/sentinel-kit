<template>
    <div class="flex h-screen w-screen bg-gray-50">
        <aside v-if="isLoggedIn" :class="{ 'w-64': !isCollapsed, 'w-20': isCollapsed }" class="flex flex-col bg-gray-800 text-white transition-all duration-300 ease-in-out shadow-lg fixed h-full z-20">
            <div class="p-4 flex justify-end">
                <button @click="isCollapsed = !isCollapsed" class="p-2 rounded-full hover:bg-gray-700 transition duration-150" :title="isCollapsed ? 'Développer' : 'Réduire'">
                    <svg v-if="isCollapsed" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                </button>
            </div>
            <nav class="flex-grow space-y-2 p-3">
                <RouterLink v-for="item in menuItems" :key="item.name" :to="{ name: item.route }" class="flex items-center p-3 rounded-lg hover:bg-gray-700 transition duration-150 group" :class="{ 'justify-center': isCollapsed }" :title="isCollapsed ? item.name : ''">
                    <span :class="`w-6 h-6 flex-shrink-0 icon-[${item.icon}] size-10 bg-white`"></span>
                    <span v-if="!isCollapsed" class="ml-4 font-medium whitespace-nowrap overflow-hidden">
                        <RouterLink :key="item.name" :to="{ name: item.route }" class="link link-primary [--link-color:orange]">{{ item.name }}</RouterLink>
                    </span>
                </RouterLink>
            </nav>
        </aside>

        <div :class="{ 'ml-64': !isCollapsed, 'ml-20': isCollapsed }" class="flex-1 flex flex-col transition-all duration-300 ease-in-out">
            <div v-if="isLoggedIn"><Header /></div>

            <main class="p-6 flex-1 overflow-y-auto">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterView, RouterLink } from 'vue-router';
import Header from './components/Header.vue';

const isLoggedIn = ref(false);
const isCollapsed = ref(true);

onMounted(() => {
    const token = localStorage.getItem('auth_token');
    isLoggedIn.value = !!token;
});

const menuItems = [
{ name: 'Home', icon: 'mdi-light--home', route: 'Home' },
{ name: 'Dashboard', icon: 'svg-spinners--blocks-wave', route: 'Home' },
{ name: 'Assets & groups', icon: 'line-md--computer-twotone', route: 'Home' },
{ name: 'Rulesets', icon: 'mdi--account-child', route: 'RulesList' },
{ name: 'Detections', icon: 'shield', route: 'Home' },
{ name: 'Users', icon: 'line-md--account', route: 'Home' },
{ name: 'Settings', icon: 'settings', route: 'Home' }
];
</script>