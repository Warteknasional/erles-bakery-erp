import http from 'k6/http';
import { check, sleep } from 'k6';

// Performance threshold configuration according to Erles Bakery ERP spec
export const options = {
  stages: [
    { duration: '30s', target: 20 },  // Ramp up to 20 virtual users
    { duration: '1m', target: 20 },   // Steady load
    { duration: '15s', target: 0 },   // Ramp down
  ],
  thresholds: {
    // 95% of requests must complete in under 250ms
    http_req_duration: ['p(95)<250'],
    // HTTP failure rate should be less than 1%
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.API_BASE_URL || 'https://backend-production-3e71.up.railway.app/api';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  // 1. Test Products catalog endpoint (Public & Admin)
  const productsRes = http.get(`${BASE_URL}/products`, { headers });
  check(productsRes, {
    'GET /api/products returns 200': (r) => r.status === 200,
    'GET /api/products latency < 250ms': (r) => r.timings.duration < 250,
  });

  sleep(1);

  // 2. Test Categories list endpoint
  const categoriesRes = http.get(`${BASE_URL}/categories`, { headers });
  check(categoriesRes, {
    'GET /api/categories returns 200': (r) => r.status === 200,
    'GET /api/categories latency < 250ms': (r) => r.timings.duration < 250,
  });

  sleep(1);

  // 3. Test Orders listing endpoint (with eager loading to verify zero N+1 overhead)
  const ordersRes = http.get(`${BASE_URL}/orders?per_page=10`, { headers });
  check(ordersRes, {
    'GET /api/orders returns 200': (r) => r.status === 200,
    'GET /api/orders latency < 250ms': (r) => r.timings.duration < 250,
  });

  sleep(1);
}
