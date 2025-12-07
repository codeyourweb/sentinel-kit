import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import "flyonui/flyonui";
import { createRouter, createWebHistory } from 'vue-router'

const app = createApp(App);

function isAuthenticated() {
    const token = localStorage.getItem('auth_token'); 
    return !!token;
}

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'Login',
            component: () => import('./views/Login.vue') 
        },
        {
            path: '/logout',
            name: 'Logout',
            component: () => import('./views/Logout.vue')
        },
        {
            path: '/',
            name: 'Home',
            component: () => import('./views/Home.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/datasources',
            name: 'Datasources',
            component: () => import('./views/Datasources.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/kibana',
            name: 'Kibana',
            component: () => import('./views/Kibana.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/grafana',
            name: 'Grafana',
            component: () => import('./views/Grafana.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/assets',
            name: 'Assets',
            component: () => import('./views/Assets.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/rules',
            name: 'RulesList',
            component: () => import('./views/RulesList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/rules/:id',
            name: 'RuleDetails',
            component: () => import('./views/RuleDetails.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/rules/:id/edit',
            name: 'RuleEdit',
            component: () => import('./views/RuleEdit.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/rules/create',
            name: 'RuleCreate',
            component: () => import('./views/RuleCreate.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/alerts',
            name: 'AlertsList',
            component: () => import('./views/AlertsList.vue'),
            meta: { requiresAuth: true }
        }

    ]
});

router.beforeEach((to, from, next) => {
    const isAuthRequired = to.meta.requiresAuth;
    const isLoggedIn = isAuthenticated();

    if (isAuthRequired && !isLoggedIn) {
        next({ name: 'Login', query: { redirect: to.fullPath } });        
    } 
    else if (to.name === 'Login' && isLoggedIn) {        
        next({ name: 'Home' });
    } 
    else {
        next();
    }
});

router.afterEach(async (to, from, failure) => {
  if (!failure) setTimeout(() => window.HSStaticMethods.autoInit(), 100);
});

app.use(router);
app.mount('#app');
