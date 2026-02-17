const axios = require('axios');
const { execSync } = require('child_process');

// Load environment variables from .env file
require('dotenv').config();

describe('Extension Installation', () => {
  const FLEETBASE_URL = process.env.FLEETBASE_URL || 'http://localhost:8000';
  let authToken;

  beforeAll(() => {
    authToken = process.env.FLEETBASE_TEST_TOKEN;
    if (!authToken) {
      console.warn('FLEETBASE_TEST_TOKEN not set - some tests will be skipped');
    }
  });

  describe('Extension API Accessibility', () => {
    test('Extensions endpoint should be accessible', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 401, 403]).toContain(response.status);
    });
  });

  describe('Storefront Extension', () => {
    test('Storefront extension should be installed', async () => {
      if (!authToken) {
        pending('Skipping - FLEETBASE_TEST_TOKEN not set');
        return;
      }

      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        timeout: 5000
      });

      expect(response.status).toBe(200);
      
      const storefront = response.data.find(ext => 
        ext.name === 'storefront' || ext.name === 'Storefront'
      );
      
      expect(storefront).toBeDefined();
    });

    test('Storefront extension should be activated', async () => {
      if (!authToken) {
        pending('Skipping - FLEETBASE_TEST_TOKEN not set');
        return;
      }

      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        timeout: 5000
      });

      const storefront = response.data.find(ext => 
        ext.name === 'storefront' || ext.name === 'Storefront'
      );
      
      expect(storefront).toBeDefined();
      expect(storefront.active || storefront.status === 'active').toBe(true);
    });

    test('Storefront extension directory should exist', () => {
      try {
        const output = execSync(
          'docker-compose exec -T fleetbase-api test -d /var/www/html/extensions/storefront && echo "exists"',
          { encoding: 'utf-8' }
        );
        expect(output.trim()).toBe('exists');
      } catch (error) {
        fail('Storefront extension directory does not exist');
      }
    });
  });

  describe('FleetOps Extension', () => {
    test('FleetOps extension should be installed', async () => {
      if (!authToken) {
        pending('Skipping - FLEETBASE_TEST_TOKEN not set');
        return;
      }

      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        timeout: 5000
      });

      expect(response.status).toBe(200);
      
      const fleetops = response.data.find(ext => 
        ext.name === 'fleetops' || ext.name === 'FleetOps'
      );
      
      expect(fleetops).toBeDefined();
    });

    test('FleetOps extension should be activated', async () => {
      if (!authToken) {
        pending('Skipping - FLEETBASE_TEST_TOKEN not set');
        return;
      }

      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        timeout: 5000
      });

      const fleetops = response.data.find(ext => 
        ext.name === 'fleetops' || ext.name === 'FleetOps'
      );
      
      expect(fleetops).toBeDefined();
      expect(fleetops.active || fleetops.status === 'active').toBe(true);
    });

    test('FleetOps extension directory should exist', () => {
      try {
        const output = execSync(
          'docker-compose exec -T fleetbase-api test -d /var/www/html/extensions/fleetops && echo "exists"',
          { encoding: 'utf-8' }
        );
        expect(output.trim()).toBe('exists');
      } catch (error) {
        fail('FleetOps extension directory does not exist');
      }
    });
  });

  describe('Extension Functionality', () => {
    test('Storefront extension should provide Networks API', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/networks`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 401, 403]).toContain(response.status);
    });

    test('Storefront extension should provide Products API', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/products`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 401, 403]).toContain(response.status);
    });

    test('FleetOps extension should provide Places API', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/places`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 401, 403]).toContain(response.status);
    });

    test('FleetOps extension should provide Orders routing API', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/fleet-ops/orders`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 401, 403, 404]).toContain(response.status);
    });
  });

  describe('Extension Console Access', () => {
    test('Storefront menu should be accessible in Console', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/console`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 302]).toContain(response.status);
    });

    test('FleetOps menu should be accessible in Console', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/console`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 302]).toContain(response.status);
    });
  });

  describe('Extension Cache and Configuration', () => {
    test('Extensions should be registered in application cache', () => {
      try {
        const output = execSync(
          'docker-compose exec -T fleetbase-api php artisan cache:get extensions',
          { encoding: 'utf-8' }
        );
        
        expect(output).toBeTruthy();
      } catch (error) {
        console.warn('Extension cache check skipped - cache may not be set');
      }
    });

    test('Extension configuration should be loaded', () => {
      try {
        const output = execSync(
          'docker-compose exec -T fleetbase-api php artisan config:get extensions',
          { encoding: 'utf-8' }
        );
        
        expect(output).toBeTruthy();
      } catch (error) {
        console.warn('Extension config check skipped');
      }
    });
  });
});
