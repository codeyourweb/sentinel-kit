<!--
/**
 * Assets View - Asset Management and Endpoint Security (Coming Soon)
 * 
 * This view will provide comprehensive asset management capabilities for
 * endpoint security and forensic analysis in the Sentinel-Kit platform.
 * Currently displays a "coming soon" page with planned features preview.
 * 
 * Planned Features:
 * - Endpoint inventory and asset discovery
 * - Agent deployment and management
 * - Forensic analysis capabilities
 * - Compliance monitoring and reporting
 * - Vulnerability assessment integration
 * - Asset lifecycle management
 * 
 * Current Status:
 * - Placeholder view with feature preview
 * - GitHub repository promotion
 * - UI/UX design showcase for future implementation
 */
-->

<template>
  <!-- Asset Management Coming Soon Page -->
  <div class="min-h-screen bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-700">
    <!-- Fork me on GitHub -->
    <a 
      href="https://github.com/codeyourweb/sentinel-kit" 
      target="_blank" 
      rel="noopener noreferrer"
      class="fixed top-4 right-4 z-50 bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg transition-all duration-300 hover:scale-105 flex items-center space-x-2 group"
    >
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"></path>
      </svg>
      <span class="font-medium group-hover:text-blue-300 transition-colors duration-200">Add a star and fork on GitHub</span>
      <svg class="w-4 h-4 opacity-70 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
      </svg>
    </a>

    <!-- Header Section -->
    <div class="container mx-auto px-6 py-12">
      <div class="text-center mb-16">
        <h1 class="text-4xl md:text-6xl font-bold text-gray-800 dark:text-white mb-4">
          Assets Management
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
          Endpoint security and forensic analysis capabilities
        </p>
        <div class="inline-flex items-center px-6 py-3 bg-blue-100 dark:bg-blue-900 rounded-full">
          <span class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse"></span>
          <span class="text-blue-700 dark:text-blue-300 font-medium">Coming Soon</span>
        </div>
      </div>

      <!-- Roadmap Timeline -->
      <div class="relative max-w-4xl mx-auto">
        <!-- Vertical Line -->
        <div class="absolute left-1/2 transform -translate-x-1/2 w-1 bg-gradient-to-b from-blue-500 via-purple-500 to-green-500 h-full opacity-30"></div>

        <!-- Feature Items -->
        <div class="space-y-16">
          <!-- Asset Inventory -->
          <div class="roadmap-item opacity-0 transform translate-y-8" data-aos="fade-up" data-aos-delay="100">
            <div class="flex items-center justify-center mb-8">
              <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center shadow-lg relative z-10">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mx-8 md:mx-16">
              <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Asset Inventory</h3>
              </div>
              <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                Comprehensive discovery and cataloging of all endpoints in your environment. 
                Automatically detect hardware specifications, installed software, running services, 
                and network configurations. Real-time asset tracking with detailed metadata 
                collection for better security posture management.
              </p>
            </div>
          </div>

          <!-- Forensic Collection -->
          <div class="roadmap-item opacity-0 transform translate-y-8" data-aos="fade-up" data-aos-delay="200">
            <div class="flex items-center justify-center mb-8">
              <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center shadow-lg relative z-10">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mx-8 md:mx-16">
              <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Forensic Collection</h3>
              </div>
              <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                Remote collection of digital evidence from endpoints with forensically sound procedures. 
                Capture system memory dumps, disk images, registry hives, and volatile data while maintaining 
                chain of custody. Automated collection workflows with customizable evidence preservation 
                and secure transport mechanisms.
              </p>
            </div>
          </div>

          <!-- YARA Detection -->
          <div class="roadmap-item opacity-0 transform translate-y-8" data-aos="fade-up" data-aos-delay="300">
            <div class="flex items-center justify-center mb-8">
              <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center shadow-lg relative z-10">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mx-8 md:mx-16">
              <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">YARA Detection Engine</h3>
              </div>
              <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                Advanced malware detection using YARA rules across memory and filesystem. 
                Real-time scanning with custom rule sets, IOC matching, and behavioral analysis. 
                Automated threat hunting with rule management, hit correlation, and detailed 
                reporting for incident response teams.
              </p>
            </div>
          </div>

          <!-- Artifacts Gathering -->
          <div class="roadmap-item opacity-0 transform translate-y-8" data-aos="fade-up" data-aos-delay="400">
            <div class="flex items-center justify-center mb-8">
              <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center shadow-lg relative z-10">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mx-8 md:mx-16">
              <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Artifacts & IR Tools</h3>
              </div>
              <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                Automated collection of digital artifacts and execution of incident response tools. 
                Extract event logs, browser history, registry entries, and system artifacts. 
                Deploy and execute IR tools remotely including Volatility, Plaso, and custom scripts 
                for comprehensive digital investigation workflows.
              </p>
            </div>
          </div>

          <!-- Network Packet Capture -->
          <div class="roadmap-item opacity-0 transform translate-y-8" data-aos="fade-up" data-aos-delay="500">
            <div class="flex items-center justify-center mb-8">
              <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center shadow-lg relative z-10">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mx-8 md:mx-16">
              <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Live Packet Capture</h3>
              </div>
              <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                Real-time network traffic monitoring and packet capture capabilities. 
                Deploy network sensors for continuous monitoring, triggered captures based on IOCs, 
                and full packet inspection. Advanced filtering, protocol analysis, and 
                integration with threat intelligence for network-based threat detection.
              </p>
            </div>
          </div>

          <!-- Suricata Detection -->
          <div class="roadmap-item opacity-0 transform translate-y-8" data-aos="fade-up" data-aos-delay="600">
            <div class="flex items-center justify-center mb-8">
              <div class="w-16 h-16 bg-indigo-500 rounded-full flex items-center justify-center shadow-lg relative z-10">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mx-8 md:mx-16">
              <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-indigo-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Suricata Live Detection</h3>
              </div>
              <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                High-performance network intrusion detection with Suricata engine integration. 
                Real-time traffic analysis, signature-based detection, and anomaly identification. 
                Custom rule management, alert correlation, and automated response capabilities 
                for comprehensive network security monitoring and threat prevention.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-20 pb-12">
        <p class="text-gray-500 dark:text-gray-400 mb-4">
          These features are currently in development
        </p>
        <div class="flex justify-center space-x-2">
          <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></span>
          <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
          <span class="w-2 h-2 bg-green-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted } from 'vue'

export default {
  name: 'Assets',
  setup() {
    onMounted(() => {
      // Intersection Observer for scroll animations
      const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      }

      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.remove('opacity-0', 'translate-y-8')
            entry.target.classList.add('opacity-100', 'translate-y-0')
          }
        })
      }, observerOptions)

      // Observe all roadmap items
      document.querySelectorAll('.roadmap-item').forEach((item) => {
        observer.observe(item)
      })

      // Cleanup
      return () => {
        observer.disconnect()
      }
    })
  }
}
</script>

<style scoped>
.roadmap-item {
  transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Pulse animation for the timeline dot */
@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
  }
}

.roadmap-item:hover .w-16 {
  animation: pulse-glow 2s infinite;
}

/* Gradient text effect */
.gradient-text {
  background: linear-gradient(45deg, #3b82f6, #8b5cf6, #10b981);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Enhanced scroll indicator */
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}
</style>