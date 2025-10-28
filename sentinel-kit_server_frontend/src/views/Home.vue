<template>
    <h1 class="text-4xl font-extrabold text-gray-900 text-left m-4">Quickstart</h1>

    <h2 class="text-2xl font-bold text-gray-800 text-left m-2 pl-4">configure & deploy</h2>

    <!-- Quickstart group -->
    <div class="card-group sm:flex *:not-last:border-e *:not-last:border-base-content/25 max-w-sm sm:max-w-full">
  <div class="card">
    <figure><img src="/images/thumbnails/deploy.png" alt="paris" /></figure>
    <div class="card-body">
      <h5 class="card-title mb-2.5">Deploy agents</h5>
      <p>Configure customizable agents with DLP, detection or response features your need on your infrastructure.</p>
    </div>
    <div class="card-footer">
      <p class="text-base-content/50 text-sm">
        <button class="btn btn-primary">Build & install</button>
      </p>
    </div>
  </div>
  <div class="card">
    <figure><img src="/images/thumbnails/detect.png" alt="rome" /></figure>
    <div class="card-body">
      <h5 class="card-title mb-2.5">Configure security policy</h5>
      <p>Detect, analyze, and respond to threats in real-time. Define security policies and automate responses to incidents.</p>
    </div>
    <div class="card-footer">
      <p class="text-base-content/50 text-sm">
        <button class="btn btn-primary">Edit policy</button>
      </p>
    </div>
  </div>
  <div class="card">
    <figure><img src="/images/thumbnails/respond.png" alt="sydney" /></figure>
    <div class="card-body">
      <h5 class="card-title mb-2.5">Investigate on your security data</h5>
      <p>Explore your security data to uncover insights and trends. Use advanced analytics to identify anomalies and potential threats.</p>
    </div>
    <div class="card-footer">
      <p class="text-base-content/50 text-sm">
        <button class="btn btn-primary">Access SIEM</button>
      </p>
    </div>
  </div>
</div>

    <h2 class="text-2xl font-bold text-gray-800 mt-10 text-left m-2 mb-0 pl-4">Current coverage</h2>
    <!-- Card for registered assets -->
    <div class="card-group sm:flex">
        <div class="card max-w-sm m-10 border">
            <div class="card-body">
                <h2 class="card-title mb-0">Registered assets</h2>
                <h3 class="text-4xl font-extrabold text-gray-900">1624</h3>
                <div class="more-info mt-4 flex text-center mx-auto">
                    <a class="link link-primary link-animated text-right">
                        <button class="btn btn-sm btn-info">View all</button>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card for detection rules -->
        <div class="card max-w-sm m-10 border">
            <div class="card-body">
                <h2 class="card-title mb-0">Active detection rules</h2>
                <h3 class="text-4xl font-extrabold text-gray-900">112</h3>
                <div class="more-info mt-4 flex text-center mx-auto">
                    <a class="link link-primary link-animated text-right">
                        <button class="btn btn-sm btn-info">Access ruleset</button>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card for detection rules -->
        <div class="card max-w-sm m-10 border">
            <div class="card-body">
                <h2 class="card-title mb-0">Open alerts</h2>
                <h3 class="text-4xl font-extrabold text-gray-900">42</h3>
                <div class="more-info mt-4 flex text-center mx-auto">
                    <a class="link link-primary link-animated text-right">
                        <button class="btn btn-sm btn-info">See detection portal</button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
const BASE_URL = import.meta.env.VITE_API_BASE_URL;
const router = useRouter();

fetch(`${BASE_URL}/user/profile`, {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
    }
})
.then(response => {
    if (response.status === 401) {
        localStorage.removeItem('auth_token');
        router.push({ name: 'Login' });
    }
})
.catch(error => {
    console.error('Error fetching user profile:', error);
});

</script>