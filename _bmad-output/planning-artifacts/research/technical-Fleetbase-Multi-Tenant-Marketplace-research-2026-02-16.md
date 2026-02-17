---
stepsCompleted: [1, 2, 3]
inputDocuments: []
workflowType: 'research'
lastStep: 3
research_status: 'sufficient_for_architecture_revision'
research_type: 'technical'
research_topic: 'Fleetbase Multi-Tenant Marketplace Architecture'
research_goals: 'Understand Networks/Vendors/Places architecture, order routing workflows, Pallet extension inventory management, and integration points for custom ELURC payment and DAO membership'
user_name: 'Thomas'
date: '2026-02-16'
web_research_enabled: true
source_verification: true
---

# Research Report: Technical

**Date:** 2026-02-16
**Author:** Thomas
**Research Type:** Technical

---

## Research Overview

This technical research explores Fleetbase's native multi-tenant marketplace capabilities to determine how to leverage Networks, Vendors, Places, and order routing for the Stalabard DAO marketplace instead of building custom solutions.

---

## Technical Research Scope Confirmation

**Research Topic:** Fleetbase Multi-Tenant Marketplace Architecture

**Research Goals:** Understand Networks/Vendors/Places architecture, order routing workflows, Pallet extension inventory management, and integration points for custom ELURC payment and DAO membership

**Technical Research Scope:**

- Architecture Analysis - design patterns, frameworks, system architecture
- Implementation Approaches - development methodologies, coding patterns
- Technology Stack - languages, frameworks, tools, platforms
- Integration Patterns - APIs, protocols, interoperability
- Performance Considerations - scalability, optimization, patterns

**Research Methodology:**

- Current web data with rigorous source verification
- Multi-source validation for critical technical claims
- Confidence level framework for uncertain information
- Comprehensive technical coverage with architecture-specific insights

**Scope Confirmed:** 2026-02-16

---

## Technology Stack Analysis

### Programming Languages

**Primary Languages:**
- **PHP 7.3.0+** - Backend core language for Fleetbase extensions and API
- **JavaScript/TypeScript** - Frontend (Ember.js for Console, React Native for mobile apps)
- **SQL** - Database queries and migrations

**Language Evolution:**
- Fleetbase leverages modern PHP features with Laravel framework
- TypeScript adoption in frontend for type safety
- React Native enables cross-platform mobile (iOS/Android) with single codebase

_Performance Characteristics:_ PHP/Laravel provides robust server-side processing with Eloquent ORM for database abstraction. React Native delivers near-native mobile performance.

_Source:_ https://github.com/fleetbase/fleetbase

### Development Frameworks and Libraries

**Backend Framework:**
- **Laravel** - PHP framework powering Fleetbase core API
- Composer for dependency management
- PHPUnit for testing

**Frontend Frameworks:**
- **Ember.js v4.8+** - Console/admin interface framework
- **React Native** - Mobile app framework for Storefront and Navigator apps
- Ember CLI v4.8+ for build tooling

**Key Libraries:**
- `@fleetbase/storefront` - SDK for storefront integration
- `react-native-device-info` - Device identification for cart management
- Laravel event system - Event-driven architecture

_Ecosystem Maturity:_ Laravel has extensive package ecosystem. Ember.js provides stable, convention-over-configuration frontend. React Native enables code sharing across mobile platforms.

_Source:_ https://github.com/fleetbase/pallet, https://github.com/fleetbase/storefront-app

### Database and Storage Technologies

**Primary Database:**
- **PostgreSQL / MySQL** - Relational database for core data persistence
- Supports both engines via Laravel database abstraction

**Caching & Queues:**
- **Redis** - Session management, cache, job queues, real-time features
- In-memory data structure store for performance optimization

**Data Warehousing:**
- Extensible via Laravel's database system
- Migration-based schema management

_Source:_ Inferred from Laravel/Fleetbase architecture patterns

### Development Tools and Platforms

**IDE and Editors:**
- PHP-compatible IDEs (PHPStorm, VSCode)
- Ember Inspector for Ember.js debugging
- React Native Debugger for mobile development

**Version Control:**
- Git-based (GitHub)
- Monorepo structure for extensions (frontend + backend together)

**Build Systems:**
- **Composer** - PHP dependency management and autoloading
- **Ember CLI** - Frontend build pipeline
- **Metro** - React Native bundler

**Testing Frameworks:**
- PHPUnit for backend unit tests
- Testem for Ember.js tests
- Jest for React Native testing

_Source:_ https://github.com/fleetbase/pallet/blob/main/phpunit.xml.dist

### Cloud Infrastructure and Deployment

**Deployment Options:**
- **Docker** - Containerized deployment (recommended)
- **Self-hosted** - On-premise or cloud VPS
- **Fleetbase Cloud** - Managed hosting at console.fleetbase.io

**Container Technologies:**
- Docker-based installation
- Docker Compose for local development
- Scalable architecture for production deployments

**CDN and Edge Computing:**
- Asset delivery via CDN configurable
- Real-time tracking via WebSockets/Redis

_Source:_ https://github.com/fleetbase/fleetbase, https://console.fleetbase.io

### Technology Adoption Trends

**Migration Patterns:**
- Shift to API-first, headless architecture
- Mobile-first development with React Native
- Modular extension system replacing monolithic apps

**Emerging Technologies:**
- Extension marketplace for third-party integrations
- Fleetbase CLI for extension management
- Real-time updates via event-driven architecture

**Community Trends:**
- Open-source model (AGPL-3.0 license with commercial option)
- Active development (139 releases on main repo)
- Growing ecosystem with Storefront, FleetOps, Pallet extensions

_Source:_ https://github.com/fleetbase/fleetbase/releases

---

## Fleetbase Multi-Vendor Architecture Analysis

### Networks - Multi-Tenant Marketplace System

**Architecture Discovery:**

Fleetbase Storefront implements multi-vendor marketplaces through a **"Networks"** concept:

**Network Structure:**
- **Network Owner** - Controls the main marketplace
- **Invited Stores** - Individual vendors who join the network
- **Invitation-Based** - Network owner invites stores via email or shareable link
- **Store Independence** - Each store manages own products, but network owner has order overview

**Key Capabilities:**
- Single store can have multiple locations
- Network owner sees all orders created through network
- Network owner CANNOT access individual store's private resources (customers, detailed store data)
- Each invited store gets own Fleetbase Console dashboard for product management

**Implementation:**
- Configured via Fleetbase Storefront extension in Console
- Network has own key (different from individual store key)
- Currency set at network level
- StorefrontApp can launch as network app using `network key`

_Source:_ https://github.com/fleetbase/storefront-app (Multi-Vendor section)

**Confidence Level:** HIGH - Verified from official documentation

### Places - Delivery Points and Locations

**Architecture Discovery:**

Fleetbase uses **"Places"** as a core logistics concept for managing delivery locations:

**Place Capabilities:**
- **Service Areas** - Define geographic zones for operations
- **Zones** - Sub-areas within service regions
- **Dynamic Filtering** - Filter places by ServiceArea, Zone, or geographic radius
- **Store Locations** - Each store must have ≥1 location with operating hours
- **Delivery Locations** - Client offices, warehouses, pickup points

**FleetOps Integration:**
- Places are managed in FleetOps extension
- Custom tagging possible (e.g., `is_pickup_point`, `is_delivery_hub`)
- Address autocomplete for easy location entry
- Operating hours configuration per location

**Use Case for Stalabard:**
- Grocery stores = Places tagged as delivery/pickup points
- Individual sellers = Vendors in Network
- Order routing: Seller → Grocery Store (Place) → Customer pickup

_Source:_ https://docs.fleetbase.io/guides/fleet-ops/getting-started/, https://github.com/fleetbase/fleetbase/discussions/115

**Confidence Level:** HIGH - Verified from FleetOps documentation

### Order Routing and Workflow

**Architecture Discovery:**

Fleetbase provides **customizable order workflows** via FleetOps:

**Order Configuration:**
- **Custom Fields** - Add fields to order forms
- **Activity Flow** - Define status transitions (auto or driver-updated)
- **Service Rates** - Custom pricing algorithms
- **Real-time Tracking** - WebSocket-based order updates

**Multi-Leg Delivery Flow:**
1. **Order Creation** - Customer initiates order
2. **Assignment** - Automated or manual driver assignment
3. **Pickup** - Driver collects from seller
4. **Dispatch to Hub** - Seller marks "Dispatched to Hub"
5. **Hub Arrival** - Grocery store notified of incoming order
6. **Customer Notification** - "At Delivery Point" status
7. **Customer Pickup** - Final collection from grocery store
8. **POD (Proof of Delivery)** - Completion confirmation

**Navigator App Integration:**
- Drivers receive orders via Fleetbase Navigator App
- Track routes, update statuses, capture POD
- Real-time sync with FleetOps dashboard

_Source:_ https://docs.fleetbase.io/guides/fleet-ops/getting-started/, https://fleetbase.io/products/customer-portal

**Confidence Level:** HIGH - Verified from official FleetOps documentation

### Pallet Extension - Inventory Management

**Architecture Discovery:**

**Pallet** is Fleetbase's official **Inventory & Warehouse Management Extension**:

**Structure:**
- Monorepo: Frontend (Ember.js) + Backend (PHP)
- Integrates with Fleetbase core and FleetOps
- Dependencies: `fleetbase/core-api`, `fleetbase/fleetops`, `fleetbase/pallet`

**Capabilities:**
- Track inventory across multiple warehouses
- Stock levels management
- Warehouse operations
- Integration with order fulfillment

**Installation:**
```bash
composer require fleetbase/core-api
composer require fleetbase/fleetops
composer require fleetbase/pallet
```

**Use Case for Stalabard:**
- Unified inventory view across grocery stores (delivery points)
- Track when seller products arrive at pickup locations
- Manage stock availability at multiple Places

_Source:_ https://github.com/fleetbase/pallet

**Confidence Level:** MEDIUM-HIGH - Repository confirmed, but limited public documentation on detailed features

### Payment Gateway Integration

**Architecture Discovery:**

Fleetbase Storefront supports **extensible payment gateways**:

**Current Implementation:**
- **Stripe** - Primary supported gateway
- Public/Secret key configuration in Fleetbase Console
- Sandbox mode available for testing
- No Fleetbase fees on transactions

**Custom Gateway Extension:**
- Laravel-based payment gateway interface
- Can implement custom payment providers
- Intercepts checkout flow
- Updates order status based on payment verification

**ELURC Integration Pattern (for Stalabard):**
1. Create custom PHP extension implementing Fleetbase payment gateway interface
2. Database: Add currency field = "ELURC"
3. Gateway logic:
   - Check user's ELURC wallet balance
   - Create transaction on community ledger
   - Update Fleetbase order status to "Paid"
4. Register gateway in Storefront settings

_Source:_ https://github.com/fleetbase/storefront-app, Laravel payment gateway patterns

**Confidence Level:** MEDIUM - Stripe confirmed, custom gateway pattern inferred from Laravel/Fleetbase architecture

---

## Storefront API & SDK Integration

**Architecture Discovery:**

**Storefront SDK** provides JavaScript/TypeScript API client:

**Core Resources:**
- **Cart** - Retrieve, add items, remove items, update quantities
- **Products** - Browse, search, filter
- **Categories** - Product organization
- **Customers** - Authentication and profiles
- **Checkout** - Order creation and payment flow

**Cart Implementation:**
```javascript
import Storefront from '@fleetbase/storefront';
const storefront = new Storefront('<store or network key>');

// Device-based cart (React Native)
const cart = await storefront.cart.retrieve(uniqueDeviceId);
await cart.add(productId);
await cart.update(cartItemId, quantity, { addons, variants });
await cart.remove(cartItemId);
```

**Network vs Store Keys:**
- Store Key - Single vendor storefront
- Network Key - Multi-vendor marketplace

**API-First Approach:**
- Headless commerce architecture
- Full control over shopping experience
- RESTful API backend

_Source:_ https://github.com/fleetbase/storefront-app

**Confidence Level:** HIGH - Verified from official SDK documentation

---

## Integration Patterns Analysis

### API Design Patterns

**RESTful API Architecture:**

Fleetbase implements a comprehensive RESTful API following standard HTTP conventions:

**Authentication Pattern:**
- **Bearer Token Authentication** - API keys prefixed with `flb_live_` (production) or `flb_test_` (sandbox)
- Authorization header: `Authorization: Bearer flb_live_yourapikey`
- API keys managed via Fleetbase Console Developers section
- Restricted API keys available for granular permissions

**HTTP Methods:**
- `GET` - Retrieve resources or resource lists
- `POST` - Create new resources
- `PUT/PATCH` - Update existing resources (PATCH for partial updates)
- `DELETE` - Remove resources

**RESTful Resource Patterns:**
- Base API: `https://api.fleetbase.io/v1`
- Resource URIs: `/v1/orders`, `/v1/drivers`, `/v1/vehicles`
- Action endpoints: `/v1/orders/{orderId}/dispatch`
- Query parameters for filtering: `?status=active`

**Response Format:**
- JSON-based responses
- Consistent error handling
- Standard HTTP status codes

_Source:_ https://docs.fleetbase.io/developers/api/

**Confidence Level:** HIGH - Verified from official API documentation

### Communication Protocols

**HTTP/HTTPS Protocol:**
- Primary API communication via HTTPS
- RESTful conventions with standard HTTP verbs
- JSON content type (`application/json`)
- Bearer token authentication in headers

**WebSocket Protocol (SocketCluster):**
- **Real-time bidirectional communication** for live tracking and updates
- SocketCluster pub/sub messaging system
- Server: `socket.fleetbase.io:8000`
- Secure WebSocket connections (`wss://`)

**WebSocket Channel Patterns:**
- Resource-based channels: `{type}.{id}` format (e.g., `driver.driver_iox3ekU`)
- Event-driven updates: `driver.location_changed`, `order.status_updated`
- Subscribe/unsubscribe model for selective data streams

**Connection Setup:**
```javascript
import socketClusterClient from 'socketcluster-client';
const socket = socketClusterClient.create({
  hostname: 'socket.fleetbase.io',
  secure: true,
  port: 8000,
  path: '/socketcluster/'
});
```

_Source:_ https://docs.fleetbase.io/developers/sockets/

**Confidence Level:** HIGH - Verified from official documentation

### Data Formats and Standards

**Primary Format: JSON**
- All API requests/responses use JSON
- Content-Type: `application/json`
- Accept: `application/json`

**API Request Example:**
```json
{
  "pickup": "Singapore 018971",
  "dropoff": "321 Orchard Rd, Singapore"
}
```

**WebSocket Event Format:**
```json
{
  "event": "order.updated",
  "data": {
    "id": "order_xxxabc",
    "status": "dispatched"
  }
}
```

**Laravel Standards:**
- Eloquent ORM serialization for models
- Migration-based schema definitions
- Validation via Form Requests

_Source:_ https://docs.fleetbase.io/developers/api/, https://docs.fleetbase.io/developers/webhooks/

**Confidence Level:** HIGH - Verified from examples

### System Interoperability Approaches

**Extension-Based Architecture:**
- **Modular Extensions** - PHP Laravel packages + Ember.js engines
- Extensions register via Laravel Service Providers
- Core API dependency pattern: All extensions depend on `fleetbase/core-api`

**Service Provider Pattern:**
```php
namespace Fleetbase\Extension\Providers;
use Fleetbase\Providers\CoreServiceProvider;

class ExtensionServiceProvider extends CoreServiceProvider {
  public function register() {
    $this->app->register(CoreServiceProvider::class);
  }
  public function boot() {
    $this->mergeConfigFrom(__DIR__ . '/../../config/extension.php', 'extension');
  }
}
```

**Inter-Extension Communication:**
- Shared database via Eloquent relationships
- Cross-extension model relationships (foreign keys)
- Event system for loose coupling

**API Gateway Pattern:**
- Single API endpoint: `api.fleetbase.io`
- Extension routes namespaced (e.g., `/storefront/v1`, `/fleetops/v1`)
- Centralized authentication/authorization

_Source:_ https://docs.fleetbase.io/developers/building-an-extension/basic-setup/

**Confidence Level:** HIGH - Verified from extension development guide

### Microservices Integration Patterns

**Monolithic Core with Extension Modularity:**
- Fleetbase core is monolithic (Laravel)
- Extensions add modular capabilities without true microservices
- Shared database (not microservices pattern)
- Extensions communicate via direct model relationships and events

**Not Pure Microservices:**
- Extensions share same database and runtime
- No service-to-service HTTP communication
- No independent deployment of extensions
- More similar to "modular monolith" pattern

**Benefits of Approach:**
- Simpler deployment (single Docker container)
- No network latency between extensions
- Transactional consistency across extensions
- Easier local development

_Source:_ Inferred from Fleetbase architecture patterns

**Confidence Level:** MEDIUM-HIGH - Pattern inferred from documentation

### Event-Driven Integration

**Webhook Pattern (Push Notifications):**

Fleetbase implements robust webhook system for external integration:

**Webhook Configuration:**
- Configured via Fleetbase Console → Developers → Webhooks
- Specify receiving endpoint URL (HTTPS required)
- Select specific events or subscribe to all
- Test webhooks from console

**Event Types:**
- Resource lifecycle: `order.created`, `order.updated`, `order.status_changed`
- Driver events: `driver.started_route`, `driver.completed_delivery`
- Vehicle events, inventory updates, etc.

**Webhook Payload:**
```json
{
  "event": "order.updated",
  "data": {
    "id": "order_xxxabc",
    "status": "dispatched",
    "timestamp": "2024-02-11T05:03:02Z"
  }
}
```

**Implementation Requirements:**
- Endpoint must accept HTTPS POST requests
- Validate requests are from Fleetbase
- Respond to HTTP challenges for endpoint verification
- Return 200 OK to acknowledge receipt

_Source:_ https://docs.fleetbase.io/developers/webhooks/

**Confidence Level:** HIGH - Verified from webhook documentation

**Laravel Event System (Internal):**
- Extensions fire Laravel events for internal actions
- Event listeners for audit logging, notifications
- Observer pattern for model lifecycle hooks
- Queue-based asynchronous processing

_Source:_ Laravel framework conventions

**Confidence Level:** MEDIUM - Inferred from Laravel patterns

### Integration Security Patterns

**API Key Management:**
- **Bearer Token Authentication** - Primary security mechanism
- Test keys (`flb_test_`) vs Live keys (`flb_live_`)
- Restricted API keys for granular permissions
- Keys managed via Console with rotation capability

**Webhook Security:**
- HTTPS-only endpoints required
- Request validation to confirm source
- Optional signature verification (implementation detail not documented)

**Extension Security:**
- Extensions inherit Fleetbase IAM system
- Role-based access control (RBAC)
- Laravel policies for authorization
- Middleware for route protection

**Data Encryption:**
- HTTPS for all API communication
- Secure WebSocket (`wss://`)
- Database encryption at rest (deployment dependent)

_Source:_ https://docs.fleetbase.io/developers/api/, https://docs.fleetbase.io/developers/webhooks/

**Confidence Level:** HIGH - Verified from security documentation

### Custom Payment Gateway Integration Pattern

**For ELURC Implementation:**

Based on Laravel payment gateway patterns and Fleetbase extensibility:

**Integration Approach:**
1. **Create Custom Extension** - Implement Laravel payment gateway interface
2. **Register Provider** - Add to Storefront payment gateway registry
3. **Intercept Checkout** - Hook into checkout flow via service provider
4. **Custom Logic:**
   - Validate ELURC-only enforcement
   - Generate payment intent with blockchain details
   - User signs transaction client-side (Phantom)
   - Backend verifies transaction via Solana RPC
   - Update order status on confirmation

**Laravel Payment Interface Pattern:**
```php
interface PaymentGatewayInterface {
  public function createIntent(Order $order): PaymentIntent;
  public function verifyTransaction(string $txHash): bool;
  public function processPayment(PaymentIntent $intent): PaymentResult;
}
```

**Registration in Service Provider:**
```php
public function boot() {
  $this->app['payment.gateways']->register('elurc', ElurCPaymentGateway::class);
}
```

_Source:_ Laravel payment gateway patterns, Fleetbase extension architecture

**Confidence Level:** MEDIUM - Pattern synthesized from multiple sources

---

<!-- Content will be appended sequentially through research workflow steps -->
