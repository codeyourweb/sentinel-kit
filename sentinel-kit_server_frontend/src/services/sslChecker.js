const SERVICES = {
  kibana: {
    name: 'Kibana',
    url: import.meta.env.VITE_KIBANA_URL,
    description: 'Log analysis'
  },
  grafana: {
    name: 'Grafana',
    url: import.meta.env.VITE_GRAFANA_URL,
    description: 'Metrics and monitoring'
  },
  backend: {
    name: 'Backend API',
    url: import.meta.env.VITE_BACKEND_URL,
    description: 'Main application backend'
  }
};

async function checkServiceSSL(serviceKey) {
  const service = SERVICES[serviceKey];
  
  if (!service) {
    throw new Error(`Service ${serviceKey} not found`);
  }

  if (!service.url) {
    return {
      service: serviceKey,
      name: service.name,
      url: 'N/A',
      status: 'error',
      error: 'URL not configured',
      description: service.description,
      lastChecked: new Date().toISOString()
    };
  }

  const backendHealthCheckUrl = `${import.meta.env.VITE_API_BASE_URL}/health-check/${serviceKey}`;
  
  try {    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    const response = await fetch(backendHealthCheckUrl, {
      method: 'GET',
      signal: controller.signal,
      credentials: 'omit',
      cache: 'no-cache'
    });
    
    clearTimeout(timeoutId);

    if (!response.ok) {
      return {
        service: serviceKey,
        name: service.name,
        url: service.url,
        status: 'backend_error',
        error: `Backend health check failed: HTTP ${response.status}`,
        description: service.description,
        lastChecked: new Date().toISOString()
      };
    }

    const result = await response.json();
    let frontendStatus;
    let finalError = result.error;
    
    if (result.status === 'healthy') {
      try {
        const quickTestController = new AbortController();
        const quickTestTimeoutId = setTimeout(() => quickTestController.abort(), 3000);
        
        await fetch(service.url, {
          method: 'HEAD',
          mode: 'no-cors',
          signal: quickTestController.signal,
          cache: 'no-cache'
        });
        
        clearTimeout(quickTestTimeoutId);
        frontendStatus = 'ok';
        
      } catch (noCorsError) {
        frontendStatus = 'ssl_error';
        finalError = 'SSL/TLS certificate needs approval - Click to approve and refresh';
      }
    } else if (result.status === 'unhealthy') {
   
      try {
        const noCorsController = new AbortController();
        const noCorsTimeoutId = setTimeout(() => noCorsController.abort(), 5000);
        
        const noCorsResponse = await fetch(service.url, { 
          method: 'HEAD', 
          mode: 'no-cors',
          signal: noCorsController.signal,
          cache: 'no-cache'
        });
        
        clearTimeout(noCorsTimeoutId);
         
        if (result.error && (
          result.error.includes('Could not resolve host') || 
          result.error.includes('Connection refused') ||
          result.error.includes('Connection failed')
        )) {
          frontendStatus = 'connection_error';
          finalError = 'Service is down';
        } else {
          frontendStatus = 'ssl_error';
          finalError = 'SSL/TLS certificate needs approval - Click to approve and refresh';
        }
        
      } catch (noCorsError) {
        if (result.httpStatus === 502) {
          frontendStatus = 'connection_error';
        } else if (result.httpStatus === 503) {
          frontendStatus = 'service_unavailable';
        } else if (result.httpStatus === 504) {
          frontendStatus = 'timeout_error';
        } else if (result.httpStatus >= 500) {
          frontendStatus = 'server_error';
        } else if (result.httpStatus >= 400) {
          frontendStatus = 'client_error';
        } else if (result.error && result.error.includes('Connection failed')) {
          frontendStatus = 'connection_error';
        } else if (result.error && result.error.includes('Could not resolve host')) {
          frontendStatus = 'connection_error';
          finalError = 'Service unreachable from backend (DNS/Network issue)';
        } else if (result.error && result.error.includes('timeout')) {
          frontendStatus = 'timeout_error';
        } else {
          frontendStatus = 'error';
        }
        if (!finalError.includes('DNS/Network issue')) {
          finalError = result.error;
        }
      }
    } else {
      frontendStatus = 'error';
    }

    const finalResult = {
      service: serviceKey,
      name: service.name,
      url: service.url,
      status: frontendStatus,
      error: finalError,
      description: service.description,
      lastChecked: new Date().toISOString(),
      httpStatus: result.httpStatus,
      backendUrl: result.url
    };
    
    return finalResult;

  } catch (error) {
    return {
      service: serviceKey,
      name: service.name,
      url: service.url,
      status: 'ssl_error',
      error: 'Backend SSL/TLS certificate needs approval - Click to approve backend certificate',
      description: service.description,
      lastChecked: new Date().toISOString(),
      approvalUrl: import.meta.env.VITE_BACKEND_URL
    };
  }
}

function getStatusFromHttpCode(statusCode) {
  if (statusCode >= 200 && statusCode < 300) return 'ok';
  if (statusCode >= 300 && statusCode < 400) return 'redirect';
  if (statusCode === 502) return 'connection_error';
  if (statusCode === 503) return 'service_unavailable';
  if (statusCode === 504) return 'timeout_error';
  if (statusCode >= 500) return 'server_error';
  if (statusCode === 404) return 'not_found';
  if (statusCode === 403) return 'forbidden';
  if (statusCode === 401) return 'unauthorized';
  if (statusCode >= 400) return 'client_error';
  return 'error';
}

function getErrorMessage(statusCode, statusText) {
  switch (statusCode) {
    case 502: return 'Bad Gateway: Service is down or unreachable';
    case 503: return 'Service Unavailable: Service is temporarily down';
    case 504: return 'Gateway Timeout: Service is not responding';
    case 500: return 'Internal Server Error: Service has internal issues';
    case 404: return 'Not Found: Service endpoint not found';
    case 403: return 'Forbidden: Access denied';
    case 401: return 'Unauthorized: Authentication required';
    case 302: return 'Redirect: Service is redirecting (typically healthy)';
    case 301: return 'Moved Permanently: Service relocated (typically healthy)';
    default: return `HTTP ${statusCode}: ${statusText || 'Unknown error'}`;
  }
}

function checkMultipleServicesSSL(services) {
  return Promise.all(services.map(service => checkServiceSSL(service)));
}

function openServiceInNewTab(serviceKey) {
  const service = SERVICES[serviceKey];
  if (service && service.url) {
    window.open(service.url, '_blank', 'noopener,noreferrer');
  }
}

async function checkAllServices() {
  const results = [];
  
  for (const serviceKey of Object.keys(SERVICES)) {
    try {
      const result = await checkServiceSSL(serviceKey);
      results.push(result);
    } catch (error) {
      results.push({
        service: serviceKey,
        name: SERVICES[serviceKey].name,
        url: SERVICES[serviceKey].url,
        status: 'error',
        error: error.message,
        description: SERVICES[serviceKey].description,
        lastChecked: new Date().toISOString()
      });
    }
  }
  
  return results;
}

function getApprovalUrl(serviceUrl, serviceResult) {
  if (serviceResult && serviceResult.approvalUrl) {
    return serviceResult.approvalUrl;
  }
  return serviceUrl;
}

export {
  SERVICES,
  checkServiceSSL,
  checkMultipleServicesSSL,
  openServiceInNewTab,
  checkAllServices,
  getApprovalUrl
};