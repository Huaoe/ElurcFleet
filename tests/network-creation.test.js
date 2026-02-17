const axios = require('axios');

// Load environment variables from .env file
require('dotenv').config();

describe('Network Creation', () => {
  const FLEETBASE_URL = process.env.FLEETBASE_URL || 'http://localhost:8000';
  const NETWORK_NAME = 'Stalabard DAO Marketplace';
  
  let authToken;
  let networkId;

  beforeAll(async () => {
    authToken = process.env.FLEETBASE_TEST_TOKEN;
    if (!authToken) {
      console.warn('FLEETBASE_TEST_TOKEN not set - some tests will be skipped');
    }
  });

  describe('Network API Accessibility', () => {
    test('Network endpoint should be accessible', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/networks`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      expect([200, 401, 403]).toContain(response.status);
    });

    test('Network endpoint should require authentication', async () => {
      const response = await axios.get(`${FLEETBASE_URL}/api/v1/networks`, {
        validateStatus: () => true,
        timeout: 5000
      });
      
      if (!authToken) {
        expect([401, 403]).toContain(response.status);
      }
    });
  });

  describe('Network Creation (Authenticated)', () => {
    beforeEach(() => {
      if (!authToken) {
        pending('Skipping authenticated tests - FLEETBASE_TEST_TOKEN not set');
      }
    });

    test('Should create network with correct name', async () => {
      const response = await axios.post(
        `${FLEETBASE_URL}/api/v1/networks`,
        {
          name: NETWORK_NAME,
          description: 'Multi-vendor marketplace for Stalabard DAO members',
          currency: 'USD'
        },
        {
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          },
          timeout: 10000
        }
      );

      expect(response.status).toBe(201);
      expect(response.data).toHaveProperty('id');
      expect(response.data.name).toBe(NETWORK_NAME);
      expect(response.data.currency).toBe('USD');
      
      networkId = response.data.id;
    });

    test('Created network should have unique Network ID', async () => {
      const response = await axios.get(
        `${FLEETBASE_URL}/api/v1/networks/${networkId}`,
        {
          headers: { 'Authorization': `Bearer ${authToken}` },
          timeout: 5000
        }
      );

      expect(response.status).toBe(200);
      expect(response.data.id).toBe(networkId);
      expect(response.data.id).toMatch(/^[a-zA-Z0-9_-]+$/);
    });

    test('Network should have API key generated', async () => {
      const response = await axios.get(
        `${FLEETBASE_URL}/api/v1/networks/${networkId}/keys`,
        {
          headers: { 'Authorization': `Bearer ${authToken}` },
          timeout: 5000
        }
      );

      expect(response.status).toBe(200);
      expect(response.data).toHaveProperty('keys');
      expect(Array.isArray(response.data.keys)).toBe(true);
      expect(response.data.keys.length).toBeGreaterThan(0);
    });

    test('Network currency should be configured', async () => {
      const response = await axios.get(
        `${FLEETBASE_URL}/api/v1/networks/${networkId}`,
        {
          headers: { 'Authorization': `Bearer ${authToken}` },
          timeout: 5000
        }
      );

      expect(response.status).toBe(200);
      expect(response.data.currency).toBe('USD');
    });

    test('Network should be retrievable in list', async () => {
      const response = await axios.get(
        `${FLEETBASE_URL}/api/v1/networks`,
        {
          headers: { 'Authorization': `Bearer ${authToken}` },
          timeout: 5000
        }
      );

      expect(response.status).toBe(200);
      expect(Array.isArray(response.data)).toBe(true);
      
      const network = response.data.find(n => n.name === NETWORK_NAME);
      expect(network).toBeDefined();
      expect(network.id).toBe(networkId);
    });
  });

  describe('Network Configuration Validation', () => {
    test('Environment should have DAO_ADDRESS configured', () => {
      expect(process.env.DAO_ADDRESS).toBe('D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq');
    });

    test('Environment should have DAO_NFT_COLLECTION configured', () => {
      expect(process.env.DAO_NFT_COLLECTION).toBe('3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c');
    });

    test('Environment should have SOLANA_RPC_URL configured', () => {
      expect(process.env.SOLANA_RPC_URL).toBeDefined();
      expect(process.env.SOLANA_RPC_URL).toMatch(/^https:\/\/api\.(devnet|mainnet-beta)\.solana\.com$/);
    });

    test('SOLANA_CONFIRMATION_DEPTH should be set to 10', () => {
      expect(process.env.SOLANA_CONFIRMATION_DEPTH).toBe('10');
    });

    test('SOLANA_VERIFICATION_TIMEOUT should be set to 300', () => {
      expect(process.env.SOLANA_VERIFICATION_TIMEOUT).toBe('300');
    });

    test('NETWORK_NAME should match created network', () => {
      expect(process.env.NETWORK_NAME).toBe(NETWORK_NAME);
    });
  });
});
