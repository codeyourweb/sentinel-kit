<template>
    <SSLChecker />
    
    <div class="flex h-screen w-screen bg-gray-50">
        <!-- Side menu -->
        <aside v-if="isLoggedIn" :class="{ 'w-64': !isCollapsed, 'w-20': isCollapsed }" class="flex flex-col bg-gray-800 text-white transition-all duration-300 ease-in-out shadow-lg fixed h-full z-20">
            <div class="p-4 flex justify-end">
                <button @click="isCollapsed = !isCollapsed" class="p-2 rounded-full hover:bg-gray-700 transition duration-150" :title="isCollapsed ? 'Développer' : 'Réduire'">
                    <svg v-if="isCollapsed" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                </button>
            </div>
            <RouterLink :to="{ name: 'Home' }" class="flex justify-center mb-4">
            <img src="/images/sentinel-kit_logo.png" alt="Sentinel Kit Logo" class="mx-auto mt-6 mb-6" :class="{ 'w-12 h-12': isCollapsed, 'w-24 h-24': !isCollapsed }" />
            </RouterLink>
            <nav class="flex-grow space-y-2 p-3">
                <RouterLink v-for="item in menuItems" :key="item.name" :to="{ name: item.route }" class="flex items-center p-3 rounded-lg hover:bg-gray-400 transition duration-150 group text-white" :class="{ 'justify-center': isCollapsed }" :title="isCollapsed ? item.name : ''">
                    <span :class="`text-white bg-gray-300 w-6 h-6 flex-shrink-0 ${item.icon} size-10`"></span>
                    <span v-if="!isCollapsed" class="ml-4 font-medium whitespace-nowrap overflow-hidden">
                        <RouterLink :key="item.name" :to="{ name: item.route }" class="link link-primary [--link-color:orange]">{{ item.name }}</RouterLink>
                    </span>
                </RouterLink>
            </nav>


            <button 
                @click="logout"
                class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-150 ease-in-out flex items-center"
                title="Log out"
                >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span v-if="!isCollapsed" class="whitespace-nowrap">Log out</span>
            </button>


        </aside>

        <div :class="{ 'ml-64': !isCollapsed, 'ml-20': isCollapsed }" class="flex-1 flex flex-col transition-all duration-300 ease-in-out">
            <main class="flex-1 overflow-y-auto">
                <RouterView @show-notification="showNotification" />
            </main>
        </div>

        <!-- Notification Modal -->
        <div 
            v-if="notification.visible" 
            class="fixed top-4 left-1/2 transform -translate-x-1/2 z-[9999] max-w-md w-full mx-4"
        >
            <div 
                class="rounded-lg shadow-lg transition-all duration-300 ease-in-out transform overflow-hidden"
                :class="{
                    'bg-blue-100 border-l-4 border-blue-500': notification.type === 'info',
                    'bg-yellow-100 border-l-4 border-yellow-500': notification.type === 'warning',
                    'bg-red-100 border-l-4 border-red-500': notification.type === 'error',
                    'bg-green-100 border-l-4 border-green-500': notification.type === 'success'
                }"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span 
                                class="w-6 h-6"
                                :class="{
                                    'icon-[material-symbols--info] text-blue-500 bg-blue-400': notification.type === 'info',
                                    'icon-[material-symbols--warning] text-yellow-500 bg-yellow-400': notification.type === 'warning',
                                    'icon-[material-symbols--error] text-red-500 bg-red-400': notification.type === 'error',
                                    'icon-[material-symbols--check-circle] text-green-500 bg-green-400': notification.type === 'success'
                                }"
                            ></span>
                        </div>
                        <div class="ml-3 flex-1">
                            <p 
                                class="text-sm font-medium"
                                :class="{
                                    'text-blue-800': notification.type === 'info',
                                    'text-yellow-800': notification.type === 'warning',
                                    'text-red-800': notification.type === 'error',
                                    'text-green-800': notification.type === 'success'
                                }"
                            >
                                {{ notification.message }}
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <a
                                @click="hideNotification"
                                class="btn-btn-sm btn-primary inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="{
                                    'text-blue-400 hover:bg-blue-200 focus:ring-blue-600': notification.type === 'info',
                                    'text-yellow-400 hover:bg-yellow-200 focus:ring-yellow-600': notification.type === 'warning',
                                    'text-red-400 hover:bg-red-200 focus:ring-red-600': notification.type === 'error',
                                    'text-green-400 hover:bg-green-200 focus:ring-green-600': notification.type === 'success'
                                }"
                            >
                                <span class="sr-only">Close</span>
                                <span class="icon-[material-symbols--close] w-4 h-4"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- notification progress-bar -->
                <div class="h-1 w-full relative overflow-hidden">
                    <div 
                        class="h-full progress-bar"
                        :class="{
                            'bg-blue-600': notification.type === 'info',
                            'bg-yellow-600': notification.type === 'warning',
                            'bg-red-600': notification.type === 'error',
                            'bg-green-600': notification.type === 'success'
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter, RouterView, RouterLink } from 'vue-router';
import SSLChecker from './components/SSLChecker.vue';

const router = useRouter();
const isLoggedIn = ref(false);
const isCollapsed = ref(true);
const BASE_URL = import.meta.env.VITE_API_BASE_URL;

// Notification system
const notification = ref({
    visible: false,
    type: 'info', // 'info', 'warning', 'error', 'success'
    message: ''
});

let notificationTimer = null;

const showNotification = ({ type, message }) => {
    if (notificationTimer) {
        clearTimeout(notificationTimer);
    }
    
    notification.value = {
        visible: true,
        type,
        message
    };
    
    notificationTimer = setTimeout(() => {
        hideNotification();
    }, 8000);
};

const hideNotification = () => {
    notification.value.visible = false;
    if (notificationTimer) {
        clearTimeout(notificationTimer);
        notificationTimer = null;
    }
};

onMounted(() => {
    const token = localStorage.getItem('auth_token');
    isLoggedIn.value = !!token;

    // if route is not login or logout, check auth
    const currentRoute = router.currentRoute.value.name;
    if (currentRoute !== 'Login' && currentRoute !== 'Logout') {
        checkAuth();
    }
});

const logout = () => {
    router.push({ name: 'Logout' });
};

// Authentication check
const checkAuth = async () => {
    try {
        const response = await fetch(`${BASE_URL}/user/profile`, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        if (response.status === 401) {
            localStorage.removeItem('auth_token')
            router.push({ name: 'Login' })
        }
    } catch (error) {
        console.error('Error checking authentication:', error)
    }
}

// Side menu items
const menuItems = [
{ name: 'Home', icon: 'icon-[svg-spinners--blocks-wave]', route: 'Home' },
{ name: 'Rulesets', icon: 'icon-[carbon--rule-draft]', route: 'RulesList' },
{ name: 'Alerts', icon: 'icon-[solar--eye-scan-broken]', route: 'AlertsList' },
{ name: 'Logs', icon: 'icon-[icon-park-outline--log]', route: 'Kibana' },
{ name: 'Perf. monitoring', icon: 'icon-[material-symbols--monitor-heart-outline]', route: 'Grafana' },
{ name: 'Endpoint detection', icon: 'icon-[line-md--computer-twotone]', route: 'Assets' }
];
</script>

<style scoped>
.progress-bar {
    width: 100%;
    animation: progress-countdown 8s linear forwards;
}

@keyframes progress-countdown {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>