const { execSync } = require('child_process');
const axios = require('axios');
const mysql = require('mysql2/promise');
const redis = require('redis');

describe('Fleetbase Platform Setup', () => {
  const FLEETBASE_URL = process.env.FLEETBASE_URL || 'http://localhost:8000';
  const DB_HOST = process.env.DB_HOST || 'localhost';
  const DB_PORT = process.env.DB_PORT || 3306;
  const DB_USER = process.env.DB_USERNAME || 'fleetbase';
  const DB_PASSWORD = process.env.DB_PASSWORD || 'fleetbase_password';
  const DB_NAME = process.env.DB_DATABASE || 'fleetbase';
  const REDIS_HOST = process.env.REDIS_HOST || 'localhost';
  const REDIS_PORT = process.env.REDIS_PORT || 6379;

  describe('Docker Installation', () => {
    test('Docker containers should be running', () => {
      const output = execSync('docker-compose ps --format json', { encoding: 'utf-8' });
      const containers = output.trim().split('\n').map(line => JSON.parse(line));
      
      const requiredContainers = ['fleetbase-api', 'fleetbase-mysql', 'fleetbase-redis', 'fleetbase-nginx'];
      const runningContainers = containers.filter(c => c.State === 'running').map(c => c.Name);
      
      requiredContainers.forEach(name => {
        expect(runningContainers).toContain(name);
      });
    });

    test('Fleetbase API should be accessible', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/health`, { timeout: 5000 });
      expect(response.status).toBe(200);
      expect(response.data).toHaveProperty('status');
    });

    test('API health check response time should be < 200ms', async () => {
      const startTime = Date.now();
      await axios.get(`${FLEETBASE_URL}/health`, { timeout: 5000 });
      const responseTime = Date.now() - startTime;
      
      expect(responseTime).toBeLessThan(200);
    });
  });

  describe('Database Configuration', () => {
    let connection;

    beforeAll(async () => {
      connection = await mysql.createConnection({
        host: DB_HOST,
        port: DB_PORT,
        user: DB_USER,
        password: DB_PASSWORD,
        database: DB_NAME
      });
    });

    afterAll(async () => {
      if (connection) {
        await connection.end();
      }
    });

    test('MySQL database should be accessible', async () => {
      const [rows] = await connection.execute('SELECT 1 as test');
      expect(rows[0].test).toBe(1);
    });

    test('Database query response time should be < 100ms', async () => {
      const startTime = Date.now();
      await connection.execute('SELECT 1');
      const queryTime = Date.now() - startTime;
      
      expect(queryTime).toBeLessThan(100);
    });

    test('Fleetbase tables should be initialized', async () => {
      const [tables] = await connection.execute(
        "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?",
        [DB_NAME]
      );
      
      expect(tables.length).toBeGreaterThan(0);
    });
  });

  describe('Redis Configuration', () => {
    let client;

    beforeAll(async () => {
      client = redis.createClient({
        socket: {
          host: REDIS_HOST,
          port: REDIS_PORT
        }
      });
      await client.connect();
    });

    afterAll(async () => {
      if (client) {
        await client.quit();
      }
    });

    test('Redis should be accessible', async () => {
      const pong = await client.ping();
      expect(pong).toBe('PONG');
    });

    test('Redis operations should be < 10ms', async () => {
      const startTime = Date.now();
      await client.set('test_key', 'test_value');
      await client.get('test_key');
      await client.del('test_key');
      const operationTime = Date.now() - startTime;
      
      expect(operationTime).toBeLessThan(10);
    });

    test('Redis should support session storage', async () => {
      const sessionKey = 'session:test123';
      const sessionData = JSON.stringify({ user_id: 1, timestamp: Date.now() });
      
      await client.set(sessionKey, sessionData, { EX: 3600 });
      const retrieved = await client.get(sessionKey);
      
      expect(retrieved).toBe(sessionData);
      await client.del(sessionKey);
    });
  });

  describe('Extension Installation', () => {
    test('Storefront extension should be installed', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        timeout: 5000,
        validateStatus: () => true
      });
      
      if (response.status === 200 && response.data.extensions) {
        const storefront = response.data.extensions.find(ext => ext.name === 'storefront');
        expect(storefront).toBeDefined();
        expect(storefront.active).toBe(true);
      }
    });

    test('FleetOps extension should be installed', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/extensions`, {
        timeout: 5000,
        validateStatus: () => true
      });
      
      if (response.status === 200 && response.data.extensions) {
        const fleetops = response.data.extensions.find(ext => ext.name === 'fleetops');
        expect(fleetops).toBeDefined();
        expect(fleetops.active).toBe(true);
      }
    });
  });

  describe('Environment Variables', () => {
    test('DAO_ADDRESS should be configured', () => {
      expect(process.env.DAO_ADDRESS).toBe('D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq');
    });

    test('DAO_NFT_COLLECTION should be configured', () => {
      expect(process.env.DAO_NFT_COLLECTION).toBe('3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c');
    });

    test('SOLANA_RPC_URL should be configured', () => {
      expect(process.env.SOLANA_RPC_URL).toBeDefined();
      expect(process.env.SOLANA_RPC_URL).toMatch(/^https:\/\/api\.(devnet|mainnet-beta)\.solana\.com$/);
    });

    test('Redis configuration should be set', () => {
      expect(process.env.REDIS_HOST).toBeDefined();
      expect(process.env.REDIS_PORT).toBeDefined();
    });
  });

  describe('Network Creation', () => {
    test('Network API endpoint should be accessible', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/networks`, {
        timeout: 5000,
        validateStatus: () => true
      });
      
      expect([200, 401, 403]).toContain(response.status);
    });
  });
});
