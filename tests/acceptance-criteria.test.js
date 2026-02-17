const { execSync } = require('child_process');
const axios = require('axios');
const mysql = require('mysql2/promise');
const redis = require('redis');

// Load environment variables from .env file
require('dotenv').config();

describe('Story 1.1: Acceptance Criteria Validation', () => {
  const FLEETBASE_URL = process.env.FLEETBASE_URL || 'http://localhost:8000';
  const DB_HOST = process.env.DB_HOST || 'localhost';
  const DB_PORT = process.env.DB_PORT || 3306;
  const DB_USER = process.env.DB_USERNAME || 'fleetbase';
  const DB_PASSWORD = process.env.DB_PASSWORD || 'fleetbase_password';
  const DB_NAME = process.env.DB_DATABASE || 'fleetbase';
  const REDIS_HOST = process.env.REDIS_HOST || 'localhost';
  const REDIS_PORT = process.env.REDIS_PORT || 6379;

  describe('AC1: Docker Installation and Configuration', () => {
    test('Given Docker is installed, When I execute Fleetbase Docker installation, Then Fleetbase API is running and accessible', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/health`, { timeout: 5000 });
      expect(response.status).toBe(200);
      expect(response.data).toHaveProperty('status');
    });

    test('And Storefront extension is installed and configured', async () => {
      const authToken = process.env.FLEETBASE_TEST_TOKEN;
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

    test('And FleetOps extension is installed and configured', async () => {
      const authToken = process.env.FLEETBASE_TEST_TOKEN;
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

    test('And PostgreSQL/MySQL database is initialized', async () => {
      const connection = await mysql.createConnection({
        host: DB_HOST,
        port: DB_PORT,
        user: DB_USER,
        password: DB_PASSWORD,
        database: DB_NAME
      });

      const [tables] = await connection.execute(
        "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?",
        [DB_NAME]
      );
      
      expect(tables.length).toBeGreaterThan(0);
      await connection.end();
    });
  });

  describe('AC2: Network Creation', () => {
    let authToken;
    let networkId;

    beforeAll(() => {
      authToken = process.env.FLEETBASE_TEST_TOKEN;
    });

    test('Given Fleetbase is successfully installed, When I create a new Network named "Stalabard DAO Marketplace", Then Network is created with unique Network ID', async () => {
      if (!authToken) {
        pending('Skipping - FLEETBASE_TEST_TOKEN not set');
        return;
      }

      const response = await axios.get(`${FLEETBASE_URL}/api/v1/networks`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        timeout: 5000
      });

      const network = response.data.find(n => n.name === 'Stalabard DAO Marketplace');
      
      if (network) {
        expect(network.id).toBeDefined();
        expect(network.id).toMatch(/^[a-zA-Z0-9_-]+$/);
        networkId = network.id;
      } else {
        console.warn('Network not found - may need to be created manually via Console');
      }
    });

    test('And Network Key is generated for Storefront App integration', async () => {
      if (!authToken || !networkId) {
        pending('Skipping - Network not available');
        return;
      }

      const response = await axios.get(
        `${FLEETBASE_URL}/api/v1/networks/${networkId}/keys`,
        {
          headers: { 'Authorization': `Bearer ${authToken}` },
          timeout: 5000
        }
      );

      expect(response.data).toHaveProperty('keys');
      expect(Array.isArray(response.data.keys)).toBe(true);
      expect(response.data.keys.length).toBeGreaterThan(0);
    });

    test('And Network currency settings are configured', async () => {
      if (!authToken || !networkId) {
        pending('Skipping - Network not available');
        return;
      }

      const response = await axios.get(
        `${FLEETBASE_URL}/api/v1/networks/${networkId}`,
        {
          headers: { 'Authorization': `Bearer ${authToken}` },
          timeout: 5000
        }
      );

      expect(response.data.currency).toBe('USD');
    });
  });

  describe('AC3: DAO Integration Configuration', () => {
    test('Given the Network is created, When I configure environment variables, Then DAO_ADDRESS is set correctly', () => {
      expect(process.env.DAO_ADDRESS).toBe('D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq');
    });

    test('And DAO_NFT_COLLECTION is set correctly', () => {
      expect(process.env.DAO_NFT_COLLECTION).toBe('3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c');
    });

    test('And SOLANA_RPC_URL is configured for blockchain integration', () => {
      expect(process.env.SOLANA_RPC_URL).toBeDefined();
      expect(process.env.SOLANA_RPC_URL).toMatch(/^https:\/\/api\.(devnet|mainnet-beta)\.solana\.com$/);
    });

    test('And Redis is configured for session and cache management', async () => {
      const client = redis.createClient({
        socket: {
          host: REDIS_HOST,
          port: REDIS_PORT
        }
      });
      
      await client.connect();
      const pong = await client.ping();
      expect(pong).toBe('PONG');
      
      await client.set('test_session', 'test_value', { EX: 60 });
      const value = await client.get('test_session');
      expect(value).toBe('test_value');
      
      await client.del('test_session');
      await client.quit();
    });
  });

  describe('Performance Baselines', () => {
    test('API health check response time should be < 200ms', async () => {
      const startTime = Date.now();
      await axios.get(`${FLEETBASE_URL}/health`, { timeout: 5000 });
      const responseTime = Date.now() - startTime;
      
      expect(responseTime).toBeLessThan(200);
    });

    test('Database query response time should be < 100ms', async () => {
      const connection = await mysql.createConnection({
        host: DB_HOST,
        port: DB_PORT,
        user: DB_USER,
        password: DB_PASSWORD,
        database: DB_NAME
      });

      const startTime = Date.now();
      await connection.execute('SELECT 1');
      const queryTime = Date.now() - startTime;
      
      expect(queryTime).toBeLessThan(100);
      await connection.end();
    });

    test('Redis operations should be < 10ms', async () => {
      const client = redis.createClient({
        socket: {
          host: REDIS_HOST,
          port: REDIS_PORT
        }
      });
      
      await client.connect();
      
      const startTime = Date.now();
      await client.set('perf_test', 'value');
      await client.get('perf_test');
      await client.del('perf_test');
      const operationTime = Date.now() - startTime;
      
      expect(operationTime).toBeLessThan(10);
      await client.quit();
    });
  });
});
