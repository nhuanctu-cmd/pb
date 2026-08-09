# 🏓 Pickleball SaaS Platform - Enterprise Architecture

> **Implementation note:** Repository này đang chạy thực tế trên **CodeIgniter 4.7 + PHP 8.2 + MySQL 8 + Bootstrap 5**. Các đoạn tham chiếu Laravel trong tài liệu bên dưới là thiết kế ý tưởng cũ và không phải chỉ dẫn để đổi framework. Khi triển khai, luôn ưu tiên `app/Config`, `app/Controllers`, `app/Services`, `app/Models`, `app/Filters` và migration của CodeIgniter hiện có.

> **Version:** 2.0.0
> **Status:** Architecture Design Document
> **Last Updated:** 2026-07-06

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [SaaS Multi-Tenant Architecture](#2-saas-multi-tenant-architecture)
3. [Database Architecture](#3-database-architecture)
4. [Event-Driven Architecture](#4-event-driven-architecture)
5. [Realtime Architecture](#5-realtime-architecture)
6. [Mobile-First Architecture](#6-mobile-first-architecture)
7. [API-First Architecture](#7-api-first-architecture)
8. [Queue & Notification Architecture](#8-queue--notification-architecture)
9. [AI Scheduling Architecture](#9-ai-scheduling-architecture)
10. [Media & Livestream Architecture](#10-media--livestream-architecture)
11. [Module Dependency Map](#11-module-dependency-map)
12. [Deployment Strategy](#12-deployment-strategy)
13. [CDN Strategy](#13-cdn-strategy)
14. [Backup Strategy](#14-backup-strategy)
15. [Scaling Strategy](#15-scaling-strategy)
16. [Security Strategy](#16-security-strategy)

---

## 1. System Architecture

### 1.1 High-Level System Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                             CLIENTS LAYER                                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐  │
│  │  Web App  │  │  PWA     │  │  Mobile  │  │  POS     │  │  3rd Party   │  │
│  │ (Vue 3/   │  │ (Offline │  │ (Flutter)│  │ (Kiosk)  │  │  Integrations│  │
│  │  React)   │  │  Ready)  │  │          │  │          │  │  (Zalo, FB)  │  │
│  └─────┬─────┘  └─────┬────┘  └────┬─────┘  └────┬─────┘  └──────┬───────┘  │
└────────┼───────────────┼───────────┼─────────────┼───────────────┼──────────┘
         │               │           │             │               │
         ▼               ▼           ▼             ▼               ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         CDN / LOAD BALANCER LAYER                            │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  CloudFront / Cloudflare CDN + AWS ALB / Nginx Load Balancer        │   │
│  │  - SSL Termination  - Rate Limiting  - WAF  - DDoS Protection      │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
         │               │           │             │               │
         ▼               ▼           ▼             ▼               ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER (Laravel 12)                        │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                 API GATEWAY / REVERSE PROXY                           │   │
│  │          api.pickball.com / admin.pickball.com / app.pickball.com    │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                    LARAVEL APPLICATION SERVERS                         │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐   │   │
│  │  │ Tenant Mgmt │ │ Branch Mgmt │ │ Booking     │ │ Payment     │   │   │
│  │  │ Tournament  │ │ Membership  │ │ POS         │ │ Community   │   │   │
│  │  │ AI Scheduler│ │ Media Mgmt  │ │ Report      │ │ Notification│   │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘   │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                    SERVICE LAYER (Domain Services)                    │   │
│  │  TenantService  BookingService  PricingService  TournamentService   │   │
│  │  PaymentService  NotificationService  AIService  MediaService       │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                    REPOSITORY LAYER (Data Access)                    │   │
│  │  TenantRepo  BookingRepo  CourtRepo  PlayerRepo  TournamentRepo    │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
         │               │           │             │               │
         ▼               ▼           ▼             ▼               ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         REALTIME LAYER                                       │
│  ┌────────────────────────┐  ┌─────────────────────────────────────────┐    │
│  │  Laravel Reverb/Soketi │  │  SSE + Server-Sent Events               │    │
│  │  WebSocket Server      │  │  Fallback for restricted networks       │    │
│  └────────────────────────┘  └─────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────────┘
         │               │           │             │               │
         ▼               ▼           ▼             ▼               ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                     INFRASTRUCTURE / DATA LAYER                               │
│                                                                              │
│  ┌──────────────────┐  ┌──────────────┐  ┌────────────────────────────┐    │
│  │   PostgreSQL     │  │    Redis     │  │   Storage (S3/MinIO)       │    │
│  │  - Primary/Replica│  │ - Cache     │  │  - Images                 │    │
│  │  - Partitioned   │  │ - Session   │  │  - Videos                 │    │
│  │  - TimescaleDB   │  │ - Queue     │  │  - Documents              │    │
│  │    extension     │  │ - Pub/Sub   │  │  - Backups                │    │
│  └──────────────────┘  └──────────────┘  └────────────────────────────┘    │
│                                                                              │
│  ┌──────────────────┐  ┌──────────────┐  ┌────────────────────────────┐    │
│  │   Elasticsearch  │  │   RabbitMQ   │  │   AI/ML Services           │    │
│  │  - Full-text     │  │  + Kafka     │  │  - Python Microservice    │    │
│  │  - Analytics     │  │  - Event Bus │  │  - OR-Tools               │    │
│  │  - Audit Logs    │  │  - Dead Letter│ │  - TensorFlow Serving     │    │
│  └──────────────────┘  └──────────────┘  └────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Technology Stack Matrix

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Frontend** | Vue 3 + Vite / React + Vite | SPA Web Application |
| **Frontend UI** | TailwindCSS + Headless UI | Responsive UI Framework |
| **Calendar** | FullCalendar | Court booking calendar |
| **Charts** | Recharts / Chart.js | Analytics & Reports |
| **Mobile** | PWA + Flutter Compatible API | Mobile experience |
| **Backend** | Laravel 12 | API + Admin + Business Logic |
| **Database** | PostgreSQL 16 | Primary Data Store |
| **Caching** | Redis 7 | Cache + Session + Queue |
| **Queue** | Redis + RabbitMQ | Async Job Processing |
| **Search** | Elasticsearch / Meilisearch | Full-text Search |
| **Realtime** | Laravel Reverb / Soketi | WebSocket Server |
| **AI** | Python FastAPI + OR-Tools | Scheduling Optimization |
| **Storage** | AWS S3 / MinIO | File Storage |
| **CDN** | CloudFront / Cloudflare | Static Asset Delivery |
| **Monitoring** | Laravel Pulse + Sentry | APM & Error Tracking |
| **CI/CD** | GitHub Actions + Docker | Automated Deployment |

---

## 2. SaaS Multi-Tenant Architecture

### 2.1 Tenant Isolation Strategy

```
┌────────────────────────────────────────────────────────────────────────┐
│                        TENANT ISOLATION STRATEGY                        │
│                                                                          │
│   ┌────────────────────────────────────────────────────────────────┐    │
│   │                    SHARED DATABASE APPROACH                     │    │
│   │  (Recommended for 95% of tenants up to 10,000 tenants)        │    │
│   │                                                                  │    │
│   │  ┌──────────────────────────────────────────────────────────┐   │    │
│   │  │  All tenants share same database instance               │   │    │
│   │  │  Every table has tenant_id column                       │   │    │
│   │  │  Row-level security via global scopes                   │   │    │
│   │  └──────────────────────────────────────────────────────────┘   │    │
│   └────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│   ┌────────────────────────────────────────────────────────────────┐    │
│   │                    HYBRID APPROACH (Enterprise)                 │    │
│   │                                                                  │    │
│   │  Small-Medium: Shared DB (free/pro plans)                      │    │
│   │  Large: Dedicated DB shard (grow plan)                         │    │
│   │  Enterprise: Dedicated DB + Instance (enterprise plan)         │    │
│   └────────────────────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Tenant Context Resolution

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        TENANT RESOLUTION PIPELINE                        │
│                                                                           │
│  Request ──► Subdomain Detection ──► Header Detection ──► Token Decode    │
│    │                                                                      │
│    ▼                                                                      │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │  TenantResolver Middleware                                        │    │
│  │  1. Extract tenant from: subdomain / header('X-Tenant') / JWT    │    │
│  │  2. Query tenant from cache (Redis) or DB                        │    │
│  │  3. Set config('tenant') globally                                │    │
│  │  4. Apply tenant_id to all queries via global scope              │    │
│  └──────────────────────────────────────────────────────────────────┘    │
│                                                                           │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │  Tenant-Aware Global Scope (Laravel Bouncer)                      │    │
│  │                                                                   │    │
│  │  class TenantScope implements Scope {                             │    │
│  │      public function apply(Builder $builder, Model $model) {      │    │
│  │          if (auth()->check() && !auth()->user()->is_superadmin) { │    │
│  │              $builder->where('tenant_id', tenant()->id);          │    │
│  │          }                                                        │    │
│  │      }                                                            │    │
│  │  }                                                                │    │
│  └──────────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.3 Tenant Provisioning Flow

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Sign Up     │───►│  Validate    │───►│  Create      │───►│  Run         │
│  (Plan sel)  │    │  Payment     │    │  Tenant DB   │    │  Migrations  │
└──────────────┘    └──────────────┘    └──────────────┘    └──────────────┘
                                                                    │
                                                                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Welcome     │◄───│  Seed        │◄───│  Configure   │◄───│  Setup       │
│  Email +     │    │  Default     │    │  Domain +    │    │  Default     │
│  Onboarding  │    │  Data        │    │  SSL         │    │  Admin User  │
└──────────────┘    └──────────────┘    └──────────────┘    └──────────────┘
```

### 2.4 Tenant Lifecycle States

```
REGISTERED ──► ACTIVE ──► SUSPENDED ──► TERMINATED
    │             │            │
    │             ├──► TRIAL   │
    │             │            │
    │             ├──► PAID    │
    │             │            │
    │             └──► EXPIRED─┘
    │
    └──► CANCELLED
```

### 2.5 Tenant Data Model

```sql
-- tenants table (core)
tenants {
    id, code (unique), name, email, phone, address, logo,
    domain, subdomain, plan_id, plan_expires_at,
    db_name, db_host, db_username, db_password,
    settings: JSON{timezone, currency, locale, date_format, ...},
    status: enum(active, trial, suspended, cancelled),
    trial_ends_at, activated_at, suspended_at,
    created_by, updated_by, deleted_at, created_at, updated_at
}

-- tenant_plans table
tenant_plans {
    id, name, code, description,
    max_branches, max_courts, max_players, max_staff,
    features: JSON{ai_scheduling, tournament, live_stream, api_access, ...},
    price_monthly, price_yearly, is_active, created_at, updated_at
}

-- tenant_subscriptions table
tenant_subscriptions {
    id, tenant_id, plan_id, status,
    starts_at, ends_at, trial_ends_at,
    cancelled_at, payment_method,
    gateway_subscription_id, created_at, updated_at
}

-- tenant_invoices table
tenant_invoices {
    id, tenant_id, subscription_id, amount, tax,
    total, status, paid_at, due_at,
    gateway_invoice_id, invoice_url, created_at
}

-- tenant_limits table (daily/monthly usage tracking)
tenant_usage {
    id, tenant_id, date, metric: enum(bookings, api_calls, storage),
    used, limit, created_at, updated_at
}
```

---

## 3. Database Architecture

### 3.1 Entity Relationship Diagram (Logical)

```
TENANTS 1──N BRANCHES 1──N COURTS 1──N BOOKING_ITEMS
  │               │                │
  │               │                └──N PRICE_TIERS
  │               │                │
  │               │                └──N COURT_MAINTENANCE
  │               │
  │               └──N BRANCH_OPENING_HOURS
  │               └──N BRANCH_HOLIDAYS
  │
  ├──N USERS 1──N USER_ROLES N──1 ROLES 1──N ROLE_PERMISSIONS N──1 PERMISSIONS
  │    │
  │    └──1 PLAYER_PROFILES 1──N MEMBERSHIPS N──1 MEMBERSHIP_PACKAGES
  │    └──1 PLAYER_WALLETS 1──N WALLET_TRANSACTIONS
  │    └──1 PLAYER_STATISTICS
  │    └──N BOOKINGS (as player_id)
  │
  ├──N SETTINGS
  ├──N AUDIT_LOGS
  ├──N MEDIA_FILES
  │
  └──N TOURNAMENTS 1──N TOURNAMENT_MATCHES
       │                     └──N TOURNAMENT_TEAMS
       │                     └──N TOURNAMENT_PLAYERS
       │
       └──N TOURNAMENT_BRACKETS

BOOKINGS 1──N BOOKING_ITEMS
  │           └──N BOOKING_ITEM_PLAYERS (for doubles)
  │
  ├──1 BOOKING_QR_CODES
  ├──N BOOKING_LOGS
  └──N BOOKING_PAYMENTS

PRICE_TIERS (Dynamic Pricing)
  ├── branch_id (nullable for global)
  ├── court_type_id (nullable)
  ├── day_of_week, start_time, end_time
  ├── price_per_hour, price_per_slot
  └── seasonal: season_start, season_end

NOTIFICATIONS (Polymorphic)
  notifications {
      id, tenant_id, notifiable_type, notifiable_id,
      type, data: JSON, channels: JSON,
      read_at, sent_at, failed_at,
      created_at, updated_at
  }
```

### 3.2 Complete Table Inventory

```sql
-- ========== CORE DOMAIN ==========
tenants, tenant_plans, tenant_subscriptions, tenant_invoices, tenant_usage
branches, branch_opening_hours, branch_holidays
users, password_resets, user_sessions
roles, permissions, role_permissions, user_roles
settings, audit_logs, media_files
password_histories, login_attempts

-- ========== FACILITY DOMAIN ==========
court_types, courts, court_images, court_maintenance
court_amenities, court_amenity_pivot
price_tiers, price_tier_overrides (for special events)
booking_settings

-- ========== BOOKING DOMAIN ==========
bookings, booking_items, booking_item_players
booking_qr_codes, booking_logs, booking_payments
booking_coupons, booking_coupon_usage
booking_waitlist, booking_blacklist
booking_recurring_templates
booking_rating_reviews

-- ========== PLAYER DOMAIN ==========
player_profiles, player_statistics, player_preferences
player_wallet, wallet_transactions
membership_packages, memberships
membership_benefits, membership_usage
membership_referrals, membership_rewards

-- ========== TOURNAMENT DOMAIN ==========
tournaments, tournament_categories
tournament_participants, tournament_teams
tournament_matches, tournament_match_scores
tournament_brackets, tournament_seeds
tournament_prizes, tournament_sponsors
tournament_rules, tournament_referees

-- ========== POS DOMAIN ==========
pos_categories, pos_products, pos_inventory
pos_orders, pos_order_items
pos_payments, pos_refunds
pos_shifts, pos_cash_drawers

-- ========== COMMUNITY DOMAIN ==========
community_posts, community_comments
community_likes, community_shares
community_groups, community_group_members
community_events, community_event_attendees

-- ========== FINANCE DOMAIN ==========
invoices, invoice_items, invoice_payments
expenses, expense_categories
revenue_reports, daily_revenue
payment_gateways, payment_transactions
refunds, payout_requests

-- ========== AI / SCHEDULING DOMAIN ==========
ai_optimization_jobs, ai_schedule_results
ai_recommendations, ai_prediction_cache
dynamic_pricing_rules, dynamic_pricing_history

-- ========== NOTIFICATION DOMAIN ==========
notifications, notification_templates
notification_channels, notification_logs
email_logs, sms_logs, push_logs

-- ========== INTEGRATION DOMAIN ==========
api_clients, api_tokens, api_logs
webhooks, webhook_logs, webhook_deliveries
integrations (zalo, facebook, google, etc.)
```

### 3.3 Partitioning Strategy

```sql
-- Time-series partitioning for high-volume tables
CREATE TABLE bookings (
    id BIGSERIAL,
    tenant_id INT NOT NULL,
    booking_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL,
    PRIMARY KEY (id, booking_date)
) PARTITION BY RANGE (booking_date);

CREATE TABLE bookings_2026_q1 PARTITION OF bookings
    FOR VALUES FROM ('2026-01-01') TO ('2026-04-01');

CREATE TABLE bookings_2026_q2 PARTITION OF bookings
    FOR VALUES FROM ('2026-04-01') TO ('2026-07-01');

-- Monthly partitions for audit_logs
CREATE TABLE audit_logs (
    id BIGSERIAL,
    tenant_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL,
    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

-- Hot/Warm/Cold data strategy:
-- Hot: Last 3 months (in memory + fast SSD)
-- Warm: 3-12 months (standard SSD)
-- Cold: > 12 months (compressed, archived)
```

### 3.4 Indexing Strategy

```sql
-- B-tree indexes (default)
CREATE INDEX idx_bookings_tenant_date ON bookings(tenant_id, booking_date, status);
CREATE INDEX idx_bookings_player ON bookings(tenant_id, player_id, booking_date);
CREATE INDEX idx_bookings_phone ON bookings(tenant_id, customer_phone);

-- Partial indexes
CREATE INDEX idx_active_bookings ON bookings(tenant_id, status)
    WHERE status NOT IN ('cancelled', 'refunded', 'completed');

-- Covering indexes (for common queries)
CREATE INDEX idx_bookings_list ON bookings(tenant_id, booking_date DESC, status, id)
    INCLUDE (customer_name, total_amount, created_at);

-- GIN indexes for JSON fields
CREATE INDEX idx_tenant_settings ON tenants USING GIN (settings);
CREATE INDEX idx_audit_log_values ON audit_logs USING GIN (old_values, new_values);

-- BRIN indexes for time-series
CREATE INDEX idx_audit_logs_brin ON audit_logs USING BRIN (created_at, tenant_id);
CREATE INDEX idx_booking_logs_brin ON booking_logs USING BRIN (created_at, booking_id);
```

---

## 4. Event-Driven Architecture

### 4.1 Event Bus Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         EVENT BUS ARCHITECTURE                           │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                   EVENT HUBS / CHANNELS                          │     │
│  │                                                                  │     │
│  │  booking.*     payment.*     member.*     court.*    system.*   │     │
│  │  user.*        notification.* tournament.*  ai.*     pos.*      │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  Producers   │  │  Event Store │  │  Consumers   │  │  Dead Letter │ │
│  │  (Services)  │─►│  (Kafka/    │─►│  (Listeners) │─►│  Queue       │ │
│  │              │  │   RabbitMQ) │  │              │  │  (DLQ)       │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Critical Event Flows

```
──── BOOKING_CREATED ────
    ├──> ValidatePaymentDetailsJob     (async)
    ├──> SendBookingConfirmationJob    (async - email + SMS)
    ├──> GenerateQrCodeJob             (async)
    ├──> CourtStatusSyncJob            (async - realtime broadcast)
    ├──> CheckinReminderJob            (delayed - 30min before)
    ├──> UpdatePlayerStatisticsJob     (async)
    ├──> CheckForDoubleBookingJob      (async - safety check)
    └──> TrackBookingAnalyticsJob      (async)

──── BOOKING_CANCELLED ────
    ├──> ProcessRefundJob              (async)
    ├──> ReleaseCourtSlotsJob          (async - realtime broadcast)
    ├──> NotifyWaitlistJob             (async)
    ├──> SendCancellationEmailJob      (async)
    └──> UpdateRevenueReportJob        (async)

──── MEMBERSHIP_EXPIRING ────
    ├──> SendRenewalReminderJob        (delayed - 7 days before)
    ├──> SendExpirationWarningJob      (delayed - 1 day before)
    ├──> AutoDowngradeMembershipJob    (delayed - on expiry)
    └──> UnlockPremiumFeaturesJob      (delayed - on renewal)

──── PAYMENT_COMPLETED ────
    ├──> UpdateWalletBalanceJob        (async)
    ├──> GenerateInvoiceJob            (async)
    ├──> SyncToAccountingJob           (async)
    ├──> SendPaymentReceiptJob         (async)
    └──> CheckReferralBonusJob         (async)

──── TOURNAMENT_STARTING ────
    ├──> NotifyParticipantsJob         (delayed - 1 hour before)
    ├──> GenerateBracketsJob           (async)
    ├──> AssignRefereesJob             (async)
    ├──> SetupLiveStreamJob            (async)
    └──> LockRegistrationJob           (delayed - at start time)

──── AI_SCHEDULE_OPTIMIZED ────
    ├──> PublishOptimizedSlotsJob      (async - realtime update)
    ├──> NotifyAffectedBookingsJob     (async)
    ├──> UpdatePricingRulesJob         (async)
    └──> GenerateReportJob             (async)
```

### 4.3 Event Definitions

```php
// Core Event Classes (app/Events/)
BookingCreated       { booking, player, items, total }
BookingCancelled     { booking, reason, refund_amount }
BookingRescheduled   { booking, old_time, new_time, diff_minutes }
BookingCheckedIn     { booking, checkin_time, items }

PaymentCompleted     { payment, booking, method, amount, gateway_response }
PaymentFailed        { payment, booking, error, attempt_count, next_retry }

MembershipActivated  { membership, player, package, starts_at, ends_at }
MembershipExpired    { membership, player, package, expired_at }

CourtStatusChanged   { court, old_status, new_status, reason }
CourtMaintenance     { court, maintenance_start, maintenance_end }

TournamentCreated    { tournament, type, bracket_type, max_players }
TournamentCompleted  { tournament, winner, runner_up, prizes }

UserRegistered       { user, tenant, source, ip, user_agent }
UserLogin            { user, tenant, ip, device, is_first_login }

AIRecommendation     { context, recommended_slots, confidence_score, pricing }
DynamicPriceUpdated  { price_tier, old_price, new_price, rules_applied }

SystemHealthAlert    { service, status, latency, error_rate, timestamp }
TenantQuotaExceeded  { tenant, metric, used, limit }
```

### 4.4 Event Bus Implementation

```php
// app/Services/EventBusService.php
class EventBusService {
    // Publish event to queue
    public function dispatch(string $event, array $payload): void {
        // 1. Store event (for audit/replay)
        EventStore::record($event, $payload);

        // 2. Dispatch to queue
        ProcessEventHandler::dispatch($event, $payload)
            ->onQueue('events');

        // 3. Broadcast realtime if needed
        if ($this->isRealtimeEvent($event)) {
            RealtimeBroadcaster::broadcast($event, $payload);
        }
    }

    // Failed event handling
    public function handleFailedEvent(string $event, array $payload, \Exception $e): void {
        // 1. Log to Dead Letter Queue
        DeadLetterQueue::push($event, $payload, $e);

        // 2. Notify admin if critical
        if ($this->isCriticalEvent($event)) {
            NotificationService::notifyAdmin(
                "Event {$event} failed after 3 retries",
                ['payload' => $payload, 'error' => $e->getMessage()]
            );
        }

        // 3. Alert monitoring
        Sentry::captureException($e, ['event' => $event, 'payload' => $payload]);
    }
}
```

---

## 5. Realtime Architecture

### 5.1 WebSocket Infrastructure

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       REALTIME INFRASTRUCTURE                            │
│                                                                           │
│   ┌────────────────────────────────────────────────────────────────┐     │
│   │                    CONNECTION MANAGER                          │     │
│   │                                                                  │     │
│   │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │     │
│   │  │  Laravel     │  │  Soketi      │  │  Pusher      │          │     │
│   │  │  Reverb      │  │  (Self-host) │  │  (Cloud)     │          │     │
│   │  │  (Primary)   │  │  (Fallback)  │  │  (Enterprise)│          │     │
│   │  └──────────────┘  └──────────────┘  └──────────────┘          │     │
│   └────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│   ┌────────────────────────────────────────────────────────────────┐     │
│   │                    CHANNEL ARCHITECTURE                         │     │
│   │                                                                  │     │
│   │  Private Channels:    Presence Channels:     Public Channels:   │     │
│   │  ────────────────     ──────────────────     ────────────────   │     │
│   │  private-tenant.{id}  presence-court.{id}    public-announcement│     │
│   │  private-user.{id}    presence-booking.{id}                     │     │
│   │  private-branch.{id}  presence-tournament.{id}                  │     │
│   │  private-admin.{id}   presence-branch.{id}                      │     │
│   └────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Realtime Event Types

```php
// ─── BOOKING REALTIME EVENTS ───
BookingCreated       ──► broadcast: private-tenant.{id}
                              │  payload: { booking, court, time, player }
                              │  listeners: Admin Dashboard, Staff POS, Player App

BookingUpdated       ──► broadcast: private-booking.{booking.id}
                              │  payload: { booking, old_status, new_status }
                              │  listeners: Player app, Admin panel

CourtAvailability    ──► broadcast: private-branch.{branch.id}
                              │  payload: { court_id, slot_time, status, price }
                              │  listeners: Booking calendar, Public page

// ─── SYSTEM REALTIME EVENTS ───
StaffNotification    ──► broadcast: private-tenant.{id}.staff
                              │  payload: { type, title, message, action_url }
                              │  listeners: All active staff sessions

PlayerNotification   ──► broadcast: private-user.{user.id}
                              │  payload: { notification }
                              │  listeners: Player app (mobile + web)

// ─── TOURNAMENT REALTIME EVENTS ───
MatchScoreUpdate     ──► broadcast: presence-tournament.{id}
                              │  payload: { match, score, status }
                              │  listeners: Spectators, Referees, Players

LiveStreamStatus     ──► broadcast: presence-court.{court.id}
                              │  payload: { is_live, stream_url, viewers }
                              │  listeners: Spectators

// ─── POS REALTIME EVENTS ───
NewOrderAlert        ──► broadcast: private-branch.{branch.id}.kitchen
                              │  payload: { order_id, items, table }
                              │  listeners: Kitchen Display System

OrderStatusUpdated   ──► broadcast: private-order.{order.id}
                              │  payload: { order_id, status, estimated_time }
                              │  listeners: Customer app, POS

// ─── AI REALTIME EVENTS ───
DynamicPriceChange   ──► broadcast: private-branch.{branch.id}
                              │  payload: { court_type, time_slot, old_price, new_price }
                              │  listeners: Booking page, Calendar

AIScheduleOptimized  ──► broadcast: private-tenant.{tenant.id}.admin
                              │  payload: { optimization_summary, recommendations }
                              │  listeners: Admin dashboard, Reports
```

### 5.3 Realtime Connection Architecture

```
┌────────────┐    ┌──────────────────┐    ┌────────────┐
│  Client    │    │  Laravel Reverb  │    │  Laravel   │
│  Browser   │───►│  WebSocket       │───►│  Backend   │
│  / Mobile  │    │  Server          │    │  (PHP)     │
└────────────┘    └──────────────────┘    └────────────┘
     │                    │                      │
     │   connect()        │                      │
     │───────────────────►│                      │
     │                    │  auth()              │
     │                    │─────────────────────►│
     │                    │  auth_response       │
     │                    │◄─────────────────────┤
     │  connected         │                      │
     │◄───────────────────┤                      │
     │                    │                      │
     │                    │           Event      │
     │                    │◄─────────────────────┤
     │  broadcast(event)  │                      │
     │◄───────────────────┤                      │
     │                    │                      │
     │                    │  Redis Pub/Sub       │
     │                    │◄─────── redis ───────┤
     │                    │                      │
```

### 5.4 PWA Offline Support

```javascript
// Service Worker Strategy (sw.js)
const CACHE_STRATEGIES = {
    // Cache First (static assets)
    'static': ['/js/*', '/css/*', '/images/*', '/fonts/*'],

    // Network First (dynamic content)
    'dynamic': ['/api/*', '/bookings/*', '/profile/*'],

    // Stale While Revalidate (non-critical)
    'stale': ['/announcements', '/courts/list'],
};

// Background Sync for offline bookings
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-bookings') {
        event.waitUntil(syncOfflineBookings());
    }
    if (event.tag === 'sync-checkin') {
        event.waitUntil(syncOfflineCheckins());
    }
});
```

### 5.5 Realtime Channel Authorization

```php
// app/Services/ChannelAuthService.php
class ChannelAuthService {
    public function authorizePrivateChannel(string $channelName, User $user): bool {
        return match(true) {
            // private-tenant.{id}
            str_starts_with($channelName, 'private-tenant.') =>
                $user->tenant_id === $this->extractId($channelName),

            // private-user.{id}
            str_starts_with($channelName, 'private-user.') =>
                $user->id === (int) $this->extractId($channelName),

            // private-branch.{id}
            str_starts_with($channelName, 'private-branch.') =>
                $user->branches()->where('branch_id', $this->extractId($channelName))->exists(),

            default => false
        };
    }

    public function authorizePresenceChannel(string $channelName, User $user): ?array {
        // Returns user info for presence channel
        return match(true) {
            str_starts_with($channelName, 'presence-court.') => [
                'id' => $user->id,
                'name' => $user->full_name,
                'avatar' => $user->avatar_url,
                'role' => 'player',
            ],
            default => null
        };
    }
}
```

---

## 6. Mobile-First Architecture

### 6.1 Responsive Design System

```css
/* TailwindCSS breakpoint strategy */
@screen sm { /* 640px - Mobile landscape */ }
@screen md { /* 768px - Tablet */ }
@screen lg { /* 1024px - Desktop */ }
@screen xl { /* 1280px - Wide desktop */ }
@screen 2xl { /* 1536px - Ultra-wide */ }

/* Component hierarchy based on viewport */
.mobile-bottom-nav { @apply fixed bottom-0 left-0 right-0 z-50 md:hidden; }
.desktop-sidebar { @apply hidden md:flex md:flex-col md:w-64; }
.tablet-adaptive-grid { @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4; }
```

### 6.2 Mobile App Architecture (Flutter Compatible)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    MOBILE APP ARCHITECTURE                                │
│                                                                           │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │                    PRESENTATION LAYER                             │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │    │
│  │  │ Screens  │  │ Widgets  │  │ Dialogs  │  │ Navigat. │        │    │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │    │
│  └──────────────────────────────────────────────────────────────────┘    │
│                              │                                            │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │                    STATE MANAGEMENT (Bloc/Provider)               │    │
│  │  BookingBloc  AuthBloc  CourtBloc  WalletBloc  TournamentBloc   │    │
│  └──────────────────────────────────────────────────────────────────┘    │
│                              │                                            │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │                    REPOSITORY LAYER                              │    │
│  │  BookingRepo  AuthRepo  CourtRepo  WalletRepo  TournamentRepo   │    │
│  └──────────────────────────────────────────────────────────────────┘    │
│                              │                                            │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │                    DATA LAYER                                    │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │    │
│  │  │ REST API │  │ WebSocket│  │ Local DB │  │ Secure Store │   │    │
│  │  │ (Dio)    │  │ (WS)     │  │ (Hive)   │  │ (Keychain)   │   │    │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │    │
│  └──────────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.3 PWA Features

```javascript
// manifest.json
{
  "name": "Pickleball Manager",
  "short_name": "PBM",
  "description": "Quản lý sân Pickleball chuyên nghiệp",
  "start_url": "/",
  "display": "standalone",
  "orientation": "portrait-primary",
  "background_color": "#ffffff",
  "theme_color": "#2563eb",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ],
  "categories": ["sports", "business"],
  "lang": "vi",
  "dir": "ltr"
}
```

### 6.4 API Contract for Mobile

```yaml
# OpenAPI 3.0 specification for mobile endpoints
endpoints:
  # Mobile-optimized endpoints with reduced payload
  GET /api/v2/mobile/bookings:
    query:
      - page
      - per_page (max: 20)
      - status
      - date_from
      - date_to
    headers:
      - X-Device-Type: ios|android|web
      - X-App-Version: 2.1.0
    response:
      meta: { current_page, last_page, total }
      data:
        - id
        - booking_code
        - court_name
        - date
        - start_time
        - end_time
        - status
        - total_amount (formatted)
        - qr_code_url
        - can_cancel
        - can_reschedule

  # Batch API for mobile efficiency
  POST /api/v2/mobile/batch:
    body: { requests: [{ method, path, body }] }
    response: { responses: [{ status, body }] }

  # Offline sync API
  POST /api/v2/mobile/sync:
    body: {
      last_sync_at,
      local_changes: [{ table, action, data, local_id }]
    }
    response: {
      server_changes: [{ table, action, data }],
      sync_conflicts: [{ local, server }],
      sync_token
    }
```

---

## 7. API-First Architecture

### 7.1 API Versioning Strategy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        API VERSIONING STRATEGY                           │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  URL-based versioning: /api/v1/, /api/v2/, /api/v3/             │     │
│  │  Accept header versioning: Accept: application/vnd.pickball+json│     │
│  │                                                                  │     │
│  │  Version Lifecycle:                                              │     │
│  │  v1 (stable) ──► v2 (current) ──► v3 (beta)                     │     │
│  │  │                 │                 │                            │     │
│  │  Deprecated        Active            Preview                     │     │
│  │  (6mo notice)                       (30d before GA)              │     │
│  └─────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.2 API Architecture Layers

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    1. CONTROLLER LAYER                          │     │
│  │  - Request validation (FormRequest)                             │     │
│  │  - Response formatting (Resource/Collection)                    │     │
│  │  - Authentication + Authorization checks                        │     │
│  │  - HTTP status codes + headers                                  │     │
│  └──────────────────────────┬──────────────────────────────────────┘     │
│                             │                                            │
│  ┌──────────────────────────▼──────────────────────────────────────┐     │
│  │                    2. SERVICE LAYER                             │     │
│  │  - Business logic orchestration                                 │     │
│  │  - Transaction management                                       │     │
│  │  - Event dispatching                                            │     │
│  │  - Cross-cutting concerns (logging, caching)                    │     │
│  └──────────────────────────┬──────────────────────────────────────┘     │
│                             │                                            │
│  ┌──────────────────────────▼──────────────────────────────────────┐     │
│  │                    3. REPOSITORY LAYER                          │     │
│  │  - Data access abstraction                                      │     │
│  │  - Query optimization                                           │     │
│  │  - Cache-aware queries                                          │     │
│  │  - Tenant scope enforcement                                     │     │
│  └──────────────────────────┬──────────────────────────────────────┘     │
│                             │                                            │
│  ┌──────────────────────────▼──────────────────────────────────────┐     │
│  │                    4. PERSISTENCE LAYER                         │     │
│  │  - Eloquent Models + Scopes                                     │     │
│  │  - Query Builder + Raw SQL (for complex)                        │     │
│  │  - Database transactions                                        │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.3 API Endpoint Structure

```php
// ========== API v1 Routes ==========
Route::prefix('api/v1')->group(function () {
    // Public endpoints (rate limited: 30/min)
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('courts/available', [CourtController::class, 'available']); // Public slots

    // Authenticated endpoints (rate limited: 60/min)
    Route::middleware('auth:api')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });

        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index']);       // List
            Route::post('/', [BookingController::class, 'store']);      // Create
            Route::get('{id}', [BookingController::class, 'show']);     // Detail
            Route::put('{id}', [BookingController::class, 'update']);   // Update
            Route::post('{id}/cancel', [BookingController::class, 'cancel']); // Cancel
            Route::post('{id}/reschedule', [BookingController::class, 'reschedule']);
            Route::post('{id}/check-in', [BookingController::class, 'checkIn']);
            Route::post('{id}/rate', [BookingController::class, 'rate']);
            Route::get('{id}/qr-code', [BookingController::class, 'qrCode']);
            Route::get('history', [BookingController::class, 'history']);
        });

        Route::prefix('courts')->group(function () {
            Route::get('/', [CourtController::class, 'index']);
            Route::get('{id}', [CourtController::class, 'show']);
            Route::get('{id}/slots', [CourtController::class, 'slots']);
            Route::get('{id}/calendar', [CourtController::class, 'calendar']);
        });

        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'show']);
            Route::get('transactions', [WalletController::class, 'transactions']);
            Route::post('top-up', [WalletController::class, 'topUp']);
            Route::post('withdraw', [WalletController::class, 'withdraw']);
        });

        Route::prefix('memberships')->group(function () {
            Route::get('packages', [MembershipController::class, 'packages']);
            Route::get('my', [MembershipController::class, 'myMembership']);
            Route::post('subscribe/{package}', [MembershipController::class, 'subscribe']);
            Route::post('cancel', [MembershipController::class, 'cancel']);
        });

        Route::prefix('tournaments')->group(function () {
            Route::get('/', [TournamentController::class, 'index']);
            Route::get('{id}', [TournamentController::class, 'show']);
            Route::post('{id}/register', [TournamentController::class, 'register']);
            Route::post('{id}/withdraw', [TournamentController::class, 'withdraw']);
            Route::get('{id}/brackets', [TournamentController::class, 'brackets']);
            Route::get('{id}/matches', [TournamentController::class, 'matches']);
        });

        Route::prefix('community')->group(function () {
            Route::get('posts', [CommunityController::class, 'posts']);
            Route::post('posts', [CommunityController::class, 'createPost']);
            Route::post('posts/{id}/comment', [CommunityController::class, 'comment']);
            Route::post('posts/{id}/like', [CommunityController::class, 'like']);
        });

        // Admin endpoints
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::prefix('tenants')->group(function () {
                Route::get('/', [Admin\TenantController::class, 'index']);
                Route::post('/', [Admin\TenantController::class, 'store']);
                Route::get('{id}', [Admin\TenantController::class, 'show']);
                Route::put('{id}', [Admin\TenantController::class, 'update']);
                Route::delete('{id}', [Admin\TenantController::class, 'destroy']);
                Route::get('{id}/analytics', [Admin\TenantController::class, 'analytics']);
            });

            Route::prefix('branches')->group(function () {
                Route::get('/', [Admin\BranchController::class, 'index']);
                Route::post('/', [Admin\BranchController::class, 'store']);
                Route::get('{id}', [Admin\BranchController::class, 'show']);
                Route::put('{id}', [Admin\BranchController::class, 'update']);
                Route::delete('{id}', [Admin\BranchController::class, 'destroy']);
                Route::get('{id}/analytics', [Admin\BranchController::class, 'analytics']);
            });

            Route::prefix('reports')->group(function () {
                Route::get('revenue', [Admin\ReportController::class, 'revenue']);
                Route::get('bookings', [Admin\ReportController::class, 'bookings']);
                Route::get('players', [Admin\ReportController::class, 'players']);
                Route::get('occupancy', [Admin\ReportController::class, 'occupancy']);
                Route::get('export', [Admin\ReportController::class, 'export']);
            });
        });
    });

    // Webhook endpoints (no auth, verified by signature)
    Route::prefix('webhooks')->group(function () {
        Route::post('payment/momo', [WebhookController::class, 'momo']);
        Route::post('payment/vnpay', [WebhookController::class, 'vnpay']);
        Route::post('payment/stripe', [WebhookController::class, 'stripe']);
        Route::post('zalo/oa', [WebhookController::class, 'zaloOA']);
    });
});
```

### 7.4 API Response Standard

```json
// Success Response
{
    "success": true,
    "data": { ... },
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 20,
        "total": 200,
        "timestamp": "2026-07-06T15:30:00+07:00"
    }
}

// Error Response
{
    "success": false,
    "error": {
        "code": "BOOKING_CONFLICT",
        "message": "Sân đã được đặt trong khung giờ này",
        "details": {
            "conflicting_booking_id": 1234,
            "court": "Sân A1",
            "time": "15:00 - 16:00"
        },
        "trace_id": "abc-123-def-456"
    }
}

// Validation Error
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Dữ liệu không hợp lệ",
        "fields": {
            "booking_date": ["Ngày đặt không được để trống"],
            "start_time": ["Giờ bắt đầu không hợp lệ"]
        }
    }
}
```

### 7.5 API Security & Rate Limiting

```php
// app/Http/Middleware/ApiRateLimiter.php
class ApiRateLimiter {
    protected array $limits = [
        'public' => [
            'requests' => 30,
            'per_minutes' => 1,
        ],
        'authenticated' => [
            'requests' => 60,
            'per_minutes' => 1,
        ],
        'premium' => [
            'requests' => 300,
            'per_minutes' => 1,
        ],
        'webhook' => [
            'requests' => 1000,
            'per_minutes' => 1,
        ],
    ];

    public function handle($request, $next) {
        $key = $this->resolveRequestSignature($request);
        $limiter = $this->resolveLimiter($request);

        $executed = RateLimiter::attempt(
            $key,
            $limiter['requests'],
            function() {},
            $limiter['per_minutes']
        );

        if (!$executed) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.',
                    'retry_after' => RateLimiter::availableIn($key)
                ]
            ], 429);
        }

        return $next($request);
    }
}
```

### 7.6 API Documentation

```yaml
# OpenAPI 3.0 - API Strategy
docs:
  format: OpenAPI 3.0 (YAML)
  tools:
    - Scribe (auto-generate from annotations)
    - Postman Collection (auto-export)
    - Stoplight Studio (visual editor)

  sections:
    - Authentication: Bearer JWT
    - Endpoints: All CRUD operations
    - Schemas: Request/Response models
    - Examples: cURL, JavaScript, PHP, Python
    - Webhooks: Event callbacks
    - Changelog: Version history

  testing:
    - Postman/Newman (integration tests)
    - Laravel Dusk API tests
    - Load Testing with K6
```

---

## 8. Queue & Notification Architecture

### 8.1 Queue Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        QUEUE ARCHITECTURE                                │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    QUEUE HIERARCHY                               │     │
│  │                                                                  │     │
│  │  high         (Priority: 10) - Payments, Bookings, Auth         │     │
│  │  default      (Priority: 5)  - General business logic           │     │
│  │  low          (Priority: 1)  - Reports, Analytics, Cleanup      │     │
│  │  notifications                - All notification delivery        │     │
│  │  events                       - Event-driven processing          │     │
│  │  media                        - Image/video processing           │     │
│  │  ai                           - AI/ML computation tasks          │     │
│  │  webhooks                     - Webhook delivery                 │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    JOB RETRY & FAILURE                            │     │
│  │                                                                  │     │
│  │  Max attempts: 3 (default)                                      │     │
│  │  Retry delays: 10s, 60s, 300s (exponential backoff)             │     │
│  │  Job timeout: 300s (default), 600s (media), 1800s (AI)          │     │
│  │  Rate limiting: 100 jobs/minute per queue                       │     │
│  │  Queue monitoring: Laravel Horizon + Laravel Pulse              │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    DEAD LETTER QUEUE (DLQ)                       │     │
│  │                                                                  │     │
│  │  Failed jobs sent to {queue}_failed_after 3 retries             │     │
│  │  DLQ monitor alerts admin every 10 failed jobs                  │     │
│  │  Manual retry from admin dashboard                              │     │
│  └─────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Queue Worker Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => true,
    ],
],

// Supervisor configuration (supervisord.conf)
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=high,default,notifications,events,media,ai,webhooks --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=8
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log

[program:laravel-horizon]
command=php /var/www/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/laravel-horizon.log
```

### 8.3 Critical Queue Jobs

```php
// ========== HIGH PRIORITY QUEUE ==========
class ProcessPaymentJob implements ShouldQueue {
    public $queue = 'high';
    public $tries = 5;

    public function handle(): void {
        DB::transaction(function () {
            $this->payment->process();
            $this->booking->markAsPaid();
            EventBus::dispatch('payment.completed', $this->payment->toArray());
        });
    }

    public function failed(\Throwable $e): void {
        $this->payment->markAsFailed($e->getMessage());
        NotificationService::notifyAdmin('Payment processing failed', [
            'payment_id' => $this->payment->id,
            'error' => $e->getMessage(),
        ]);
    }
}

class SendBookingConfirmationJob implements ShouldQueue {
    public $queue = 'notifications';

    public function handle(): void {
        // Send via multiple channels
        $this->sendEmail();  // via queue
        $this->sendSMS();    // via queue
        $this->sendPush();   // via queue
        $this->sendZalo();   // via queue
    }

    private function sendEmail(): void {
        Mail::to($this->booking->customer_email)
            ->send(new BookingConfirmationMail($this->booking));
    }

    private function sendSMS(): void {
        SmsService::send(
            $this->booking->customer_phone,
            "Đặt sân {$this->booking->court->name} thành công! Mã: {$this->booking->booking_code}"
        );
    }
}

// ========== DELAYED JOBS ==========
class SendCheckinReminderJob implements ShouldQueue {
    public $delay = 1800; // 30 minutes before

    public function handle(): void {
        NotificationService::sendToPlayer(
            $this->booking->player,
            "Nhắc nhở: Bạn có lịch đánh sân {$this->booking->court->name} lúc {$this->booking->start_time} hôm nay!",
            ['booking_id' => $this->booking->id]
        );
    }
}

class AutoCancelUnpaidBookingJob implements ShouldQueue {
    public $delay = 900; // 15 minutes after creation

    public function handle(): void {
        if ($this->booking->payment_status === 'unpaid') {
            $this->booking->cancel('auto_cancel_unpaid');
            EventBus::dispatch('booking.auto_cancelled', $this->booking->toArray());
        }
    }
}

// ========== BATCH JOBS ==========
class GenerateDailyReportJob implements ShouldQueue {
    public $queue = 'low';

    public function handle(): void {
        $report = ReportGenerator::forTenant($this->tenantId)
            ->setDate($this->date)
            ->include('revenue', 'bookings', 'occupancy', 'players')
            ->generate();

        Cache::put("report:daily:{$this->tenantId}:{$this->date}", $report, 86400);

        if ($report->hasAnomalies()) {
            NotificationService::notifyAdmin('Daily Report Anomalies', $report->anomalies());
        }
    }
}

// ========== CHAINED JOBS ==========
class BookingCreationPipeline {
    public function handle(Booking $booking): void {
        $chain = [
            new ValidateBookingJob($booking),
            new ProcessPaymentJob($booking->payment),
            new SendBookingConfirmationJob($booking),
            new GenerateQrCodeJob($booking),
            new SyncToCalendarJob($booking),
            new UpdateCourtStatusJob($booking->court),
            new TrackAnalyticsJob($booking),
        ];

        Bus::chain($chain)
            ->onConnection('redis')
            ->onQueue('high')
            ->catch(function (\Throwable $e) use ($booking) {
                // Rollback booking if chain fails
                $booking->markAsFailed($e->getMessage());
                EventBus::dispatch('booking.creation_failed', [
                    'booking' => $booking->toArray(),
                    'error' => $e->getMessage(),
                ]);
            })
            ->dispatch();
    }
}
```

### 8.4 Notification Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      NOTIFICATION ARCHITECTURE                           │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    NOTIFICATION TYPES                            │     │
│  │                                                                  │     │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌──────┐  │     │
│  │  │ In-App  │  │  Email  │  │  SMS    │  │  Push   │  │ Zalo │  │     │
│  │  │ (Bell)  │  │(SendGrid)│  │(Twilio) │  │(FCM/APN)│  │ (OA) │  │     │
│  │  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └──────┘  │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    NOTIFICATION TEMPLATES                        │     │
│  │                                                                  │     │
│  │  Database: notification_templates table                         │     │
│  │  - id, tenant_id, code, name                                   │     │
│  │  - channels: JSON{email, sms, push, zalo, in_app}              │     │
│  │  - subject_vi, subject_en                                       │     │
│  │  - body_vi, body_en (with {placeholders})                       │     │
│  │  - is_active, created_at, updated_at                            │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    NOTIFICATION DELIVERY                          │     │
│  │                                                                  │     │
│  │  1. NotificationService::send(user, 'booking.confirmed', data)   │     │
│  │  2. Resolve template for locale                                  │     │
│  │  3. Render with data placeholders                                │     │
│  │  4. Determine channels (user prefs + template config)            │     │
│  │  5. Dispatch channel-specific jobs to 'notifications' queue      │     │
│  │  6. Log delivery status (sent_at, failed_at, error)             │     │
│  └─────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.5 Notification Flow Diagrams

```
──── NOTIFICATION: BOOKING_CONFIRMED ────
Trigger: Booking created successfully

Channels:
├── Email:      Xác nhận đặt sân [Mã: {booking_code}]
│               └── Template: email.booking_confirmed
│               └── Attachments: QR Code image, Invoice PDF
│
├── SMS:        Pickleball: Đặt sân {court_name} {start_time}-{end_time}
│               thành công. Mã: {booking_code}
│
├── In-App:     🔔 Đặt sân thành công!
│               └── Click để xem chi tiết
│               └── Actions: [Xem chi tiết] [Thêm vào lịch]
│
├── Push:       Bạn có lịch đánh sân mới!
│               └── Deep link: /bookings/{id}
│
└── Zalo OA:    Đặt sân {court_name} thành công
                └── Zalo template message

──── NOTIFICATION: CHECKIN_REMINDER ────
Trigger: 60 minutes before booking start

Channels:
├── Push:       ⏰ Sắp đến giờ đánh sân! {court_name} lúc {start_time}
│               └── Actions: [Check-in ngay] [Hủy đặt sân]
│
├── SMS:        Nhắc lịch: {court_name} - {start_time}.
│               Check-in QR: {qr_code_url}
│
└── In-App:     ⏰ Còn {minutes} phút nữa đến lịch đánh sân

──── NOTIFICATION: MEMBERSHIP_EXPIRING ────
Trigger: 7 days before expiry

Channels:
├── Email:      Thành viên {package_name} sắp hết hạn
│               └── Actions: [Gia hạn ngay] [Xem gói khác]
│
├── In-App:     ⚠️ Gói thành viên của bạn sắp hết hạn
│               └── Còn {days} ngày
│
├── SMS:        Gói tập {package_name} sắp hết hạn sau {days} ngày
│
└── Push:       Đừng bỏ lỡ! Gia hạn gói tập ngay để nhận ưu đãi
```

### 8.6 Notification Template System

```php
// app/Services/NotificationService.php
class NotificationService {
    // Available notification types
    const TYPES = [
        'booking.confirmed'      => ['email', 'sms', 'push', 'in_app', 'zalo'],
        'booking.cancelled'      => ['email', 'sms', 'push', 'in_app'],
        'booking.reminder'       => ['push', 'sms', 'in_app'],
        'booking.rescheduled'    => ['email', 'sms', 'push', 'in_app'],
        'booking.checkin'        => ['push', 'in_app'],
        'booking.rating_request' => ['email', 'push'],
        'payment.received'       => ['email', 'in_app'],
        'payment.failed'         => ['email', 'sms', 'push'],
        'payment.refund'         => ['email', 'in_app'],
        'membership.activated'   => ['email', 'in_app'],
        'membership.expiring'    => ['email', 'sms', 'push', 'in_app'],
        'membership.expired'     => ['email', 'in_app'],
        'membership.renewed'     => ['email', 'in_app'],
        'wallet.credited'        => ['in_app', 'push'],
        'wallet.debited'         => ['in_app', 'push'],
        'tournament.created'     => ['email', 'push'],
        'tournament.reminder'    => ['email', 'sms', 'push'],
        'tournament.result'      => ['email', 'in_app'],
        'community.reply'        => ['in_app', 'push'],
        'community.like'         => ['in_app'],
        'system.maintenance'     => ['email', 'sms', 'in_app'],
        'admin.alert'            => ['email', 'sms'],
    ];

    public function send(
        Notifiable $notifiable,
        string $type,
        array $data = [],
        ?array $channels = null
    ): void {
        $template = $this->resolveTemplate($type, $notifiable->locale);
        $channels = $channels ?? $template->channels;

        foreach ($channels as $channel) {
            $jobClass = match($channel) {
                'email'  => SendEmailJob::class,
                'sms'    => SendSmsJob::class,
                'push'   => SendPushNotificationJob::class,
                'in_app' => CreateInAppNotificationJob::class,
                'zalo'   => SendZaloMessageJob::class,
            };

            $jobClass::dispatch(
                $notifiable,
                $this->render($template, $data, $channel),
                $data
            )->onQueue('notifications');
        }

        // Log notification
        $this->logNotification($notifiable, $type, $channels, $data);
    }

    private function resolveTemplate(string $type, string $locale): NotificationTemplate {
        return Cache::remember(
            "notification_template:{$type}:{$locale}",
            3600,
            fn() => NotificationTemplate::where('code', $type)->first()
        );
    }
}
```

---

## 9. AI Scheduling Architecture

### 9.1 AI Microservice Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      AI SCHEDULING MICROSERVICE                          │
│                                                                           │
│   ┌───────────────────────────────────────────────────────────────┐      │
│   │                  PYTHON FASTAPI SERVICE                        │      │
│   │  Host: ai-api.pickball.com:8000                              │      │
│   │  Framework: FastAPI + Celery + Redis                         │      │
│   │  Solver: Google OR-Tools                                     │      │
│   │  ML: TensorFlow Lite (for predictions)                       │      │
│   └───────────────────┬───────────────────────────────────────────┘      │
│                       │                                                  │
│   ┌───────────────────▼───────────────────────────────────────────┐      │
│   │                    AI ENDPOINTS                                │      │
│   │                                                                      │
│   │  POST /api/ai/v1/optimize-schedule     # Main scheduling            │
│   │  POST /api/ai/v1/predict-demand        # Demand forecasting        │
│   │  POST /api/ai/v1/optimize-pricing      # Dynamic pricing           │
│   │  POST /api/ai/v1/recommend-slots       # Player recommendations    │
│   │  POST /api/ai/v1/detect-anomalies      # Fraud/anomaly detection   │
│   │  GET  /api/ai/v1/health                # Health check               │
│   └────────────────────────────────────────────────────────────────┘      │
│                                                                           │
│   ┌────────────────────────────────────────────────────────────────┐      │
│   │                  OR-TOOLS SOLVER ENGINE                          │      │
│   │                                                                      │
│   │  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐      │
│   │  │ Court Assignment │  │ Time Allocation  │  │ Resource     │      │
│   │  │ Problem          │  │ Problem          │  │ Optimization │      │
│   │  └──────────────────┘  └──────────────────┘  └──────────────┘      │
│   │                                                                      │
│   │  Constraints:                                                       │
│   │  - Court availability + maintenance schedule                        │
│   │  - Staff availability (referees, coaches)                           │
│   │  - Player level matching (beginner/intermediate/advanced)           │
│   │  - Minimum gap between bookings (15min)                             │
│   │  - Peak/off-peak hour preferences                                   │
│   │  - Recurring booking patterns                                       │
│   └────────────────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.2 AI Scheduling Flow

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  Laravel         │     │  Python AI       │     │  OR-Tools        │
│  Backend         │     │  Microservice    │     │  Solver          │
└──────┬───────────┘     └──────┬───────────┘     └──────┬───────────┘
       │                        │                        │
       │  POST /optimize        │                        │
       │  {tenant_id, date,     │                        │
       │   courts, bookings,    │                        │
       │   preferences}         │                        │
       │───────────────────────►│                        │
       │                        │                        │
       │                        │  Build Model           │
       │                        │  - Variables           │
       │                        │  - Constraints         │
       │                        │  - Objective           │
       │                        │───────────────────────►│
       │                        │                        │
       │                        │  Solve CP-SAT          │
       │                        │◄───────────────────────┤
       │                        │                        │
       │                        │  Parse Solution        │
       │                        │  - Optimal slots       │
       │                        │  - Confidence score    │
       │                        │  - Alternative plans   │
       │                        │                        │
       │  200 {schedule,        │                        │
       │   confidence,          │                        │
       │   alternatives,        │                        │
       │   execution_time}      │                        │
       │◄───────────────────────┤                        │
       │                        │                        │
       │  Dispatch Jobs         │                        │
       │  - Update Court Slots  │                        │
       │  - Notify Players      │                        │
       │  - Update Calendar     │                        │
       │  - Cache Results       │                        │
       │                        │                        │
```

### 9.3 Scheduling Solver (Python)

```python
# ai-service/scheduler/optimizer.py
from ortools.sat.python import cp_model
from datetime import datetime, timedelta
from typing import List, Dict, Any

class CourtScheduler:
    def __init__(self, config: Dict[str, Any]):
        self.model = cp_model.CpModel()
        self.solver = cp_model.CpSolver()
        self.solver.parameters.max_time_in_seconds = config.get('timeout', 30)
        self.solver.parameters.num_search_workers = config.get('workers', 8)

    def optimize(self, request: Dict) -> Dict:
        """
        Input: {
            courts: [{id, type, amenities}],
            existing_bookings: [{court_id, start, end}],
            requests: [{duration, preferred_time, level}],
            constraints: {min_gap, max_capacity, staff_available},
            scoring: {utilization_weight, satisfaction_weight, revenue_weight}
        }

        Output: {
            schedule: [{court_id, request_id, start, end}],
            utilization_rate: float,
            conflict_resolved: int,
            objective_value: float
        }
        """
        variables = self._create_variables(request)
        constraints = self._add_constraints(request, variables)
        objective = self._set_objective(request, variables)

        status = self.solver.Solve(self.model)

        if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
            return self._extract_solution(variables, status, request)
        else:
            return {
                'status': 'infeasible',
                'message': 'Could not find feasible schedule',
                'alternatives': self._generate_alternatives(request)
            }

    def _create_variables(self, request: Dict):
        # Create binary variables for each possible assignment
        variables = {}
        for request_item in request['requests']:
            for court in request['courts']:
                for slot in self._generate_slots(request_item, court, request):
                    var_name = f"x_{request_item['id']}_{court['id']}_{slot['start']}"
                    variables[var_name] = self.model.NewBoolVar(var_name)
        return variables

    def _add_constraints(self, request, variables):
        # Each request assigned to exactly one slot
        for req in request['requests']:
            req_vars = [v for k, v in variables.items()
                       if k.startswith(f"x_{req['id']}")]
            self.model.AddExactlyOne(req_vars)

        # No overlapping assignments for same court
        for court in request['courts']:
            for time_slot in self._get_time_slots():
                court_vars = [v for k, v in variables.items()
                            if f"_{court['id']}_" in k and time_slot in k]
                if court_vars:
                    self.model.Add(sum(court_vars) <= 1)

        # Minimum gap between bookings
        # ... (additional constraints)

    def _set_objective(self, request, variables):
        # Multi-objective optimization
        objectives = []

        # Maximize court utilization
        utilization = sum(variables.values()) * 0.4

        # Maximize player satisfaction (preferred times)
        satisfaction = sum(
            var * self._get_preference_score(var_name, request)
            for var_name, var in variables.items()
        ) * 0.35

        # Maximize revenue
        revenue = sum(
            var * self._get_revenue(var_name, request)
            for var_name, var in variables.items()
        ) * 0.25

        self.model.Maximize(utilization + satisfaction + revenue)
```

### 9.4 Demand Forecasting

```python
# ai-service/predictor/demand_forecast.py
class DemandForecaster:
    def predict(self, tenant_id: int, date: str) -> Dict:
        """
        Predict demand for a specific date:
        - Expected bookings per hour
        - Peak times
        - Court type preferences
        - Revenue forecast
        - Staff required
        """
        features = self._extract_features(tenant_id, date)

        # Use ensemble of models
        predictions = {
            'hourly_demand': self._predict_hourly(features),
            'court_type_split': self._predict_court_types(features),
            'revenue_estimate': self._predict_revenue(features),
            'peak_hours': self._identify_peak_hours(features),
            'staff_needed': self._calculate_staff_needs(features),
            'confidence': 0.85  # Model confidence score
        }

        return predictions

    def _extract_features(self, tenant_id: int, date: str) -> Dict:
        # Historical data from Laravel API
        history = self._fetch_booking_history(tenant_id, days_back=90)

        return {
            'day_of_week': self._day_of_week(date),
            'is_holiday': self._is_holiday(date),
            'season': self._get_season(date),
            'weather': self._get_weather_forecast(date),
            'avg_weekday_bookings': self._avg_by_day(history, 'weekday'),
            'avg_weekend_bookings': self._avg_by_day(history, 'weekend'),
            'trend_direction': self._calculate_trend(history),
            'special_events': self._get_events(date),
            'membership_ratio': self._calc_membership_ratio(history),
        }
```

### 9.5 Dynamic Pricing Engine

```python
# ai-service/pricing/dynamic_pricing.py
class DynamicPricingEngine:
    def optimize_pricing(self, request: Dict) -> Dict:
        """
        Input: {
            tenant_id, branch_id, date,
            court_types, current_occupancy,
            historical_data, competitor_prices,
            special_events
        }

        Output: {
            prices: [{court_type_id, time_slot, base_price,
                      dynamic_price, surge_multiplier}],
            expected_revenue: float,
            expected_occupancy: float
        }
        """

        # Base pricing from price_tiers table
        base_prices = self._get_base_prices(request)

        # Calculate demand multiplier
        demand_multiplier = self._calculate_demand_multiplier({
            'occupancy_rate': request['current_occupancy'],
            'day_of_week': request['date'].weekday(),
            'is_peak_season': self._is_peak_season(request['date']),
            'is_holiday': self._is_holiday(request['date']),
            'weather_score': self._get_weather_factor(request['date']),
            'events_nearby': self._get_events_factor(request['date']),
        })

        # Calculate time-based multiplier
        time_multiplier = self._calculate_time_multiplier({
            'morning': 0.8,     # 6:00-10:00 (discount)
            'midday': 1.0,      # 10:00-14:00 (standard)
            'afternoon': 1.2,   # 14:00-18:00 (premium)
            'evening': 1.5,     # 18:00-22:00 (peak)
        })

        # Apply surge pricing based on real-time demand
        surge_multiplier = self._calculate_surge(
            request['current_occupancy'],
            threshold_high=0.8,    # 80% occupancy = surge
            threshold_low=0.3,     # 30% occupancy = discount
            surge_max=2.0,
            discount_min=0.6
        )

        # Calculate final prices
        optimized_prices = []
        for court_type in request['court_types']:
            for slot in self._generate_time_slots():
                base = base_prices[court_type['id']]
                dynamic_price = base * demand_multiplier * \
                              time_multiplier * surge_multiplier

                optimized_prices.append({
                    'court_type_id': court_type['id'],
                    'time_slot': slot,
                    'base_price': base,
                    'dynamic_price': round(dynamic_price, -2),  # Round to nearest 100
                    'surge_multiplier': surge_multiplier,
                    'demand_level': 'high' if surge_multiplier > 1.2 else \
                                   'medium' if surge_multiplier > 0.8 else 'low'
                })

        return {
            'prices': optimized_prices,
            'expected_revenue': self._project_revenue(optimized_prices, request),
            'expected_occupancy': self._project_occupancy(optimized_prices, request),
            'confidence_score': 0.88
        }
```

### 9.6 AI Integration with Laravel

```php
// app/Services/AIService.php
class AIService {
    protected string $baseUrl = 'http://ai-api:8000/api/ai/v1';

    public function optimizeSchedule(int $tenantId, string $date): array
    {
        $data = [
            'tenant_id' => $tenantId,
            'date' => $date,
            'courts' => Court::where('tenant_id', $tenantId)
                ->available()
                ->get()
                ->toArray(),
            'existing_bookings' => Booking::where('tenant_id', $tenantId)
                ->whereDate('booking_date', $date)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->get()
                ->toArray(),
            'requests' => $this->getPendingRequests($tenantId, $date),
            'constraints' => [
                'min_gap_minutes' => 15,
                'opening_hours' => Branch::getOpeningHours($tenantId),
                'maintenance_slots' => CourtMaintenance::getForDate($tenantId, $date),
            ],
        ];

        $response = Http::timeout(60)
            ->retry(2, 100)
            ->post("{$this->baseUrl}/optimize-schedule", $data);

        if ($response->successful()) {
            $result = $response->json();

            // Process and store results
            $this->storeOptimizationResult($tenantId, $date, $result);

            // Cache for quick retrieval
            Cache::put(
                "ai_schedule:{$tenantId}:{$date}",
                $result,
                now()->addHours(2)
            );

            return $result;
        }

        Log::error('AI scheduling failed', [
            'tenant_id' => $tenantId,
            'date' => $date,
            'response' => $response->body(),
        ]);

        return $this->getFallbackSchedule($tenantId, $date);
    }

    public function predictDemand(int $tenantId, string $date): array
    {
        $cacheKey = "ai_demand:{$tenantId}:{$date}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $date) {
            $data = [
                'tenant_id' => $tenantId,
                'date' => $date,
                'historical_data' => $this->getHistoricalData($tenantId, 90),
            ];

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/predict-demand", $data);

            return $response->successful() ? $response->json() : $this->getFallbackDemand($tenantId, $date);
        });
    }

    public function getDynamicPrices(int $tenantId, int $branchId, string $date): array
    {
        $cacheKey = "dynamic_prices:{$tenantId}:{$branchId}:{$date}";

        return Cache::remember($cacheKey, 1800, function () use ($tenantId, $branchId, $date) {
            $occupancy = Booking::where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->whereDate('booking_date', $date)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->count();

            $totalCourts = Court::where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->available()
                ->count();

            $data = [
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'date' => $date,
                'current_occupancy' => $totalCourts > 0 ? $occupancy / $totalCourts : 0,
                'court_types' => CourtType::where('tenant_id', $tenantId)
                    ->active()
                    ->get()
                    ->toArray(),
                'historical_data' => $this->getPricingHistory($tenantId, $branchId),
            ];

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/optimize-pricing", $data);

            return $response->successful() ? $response->json()['prices'] : $this->getBasePrices($tenantId, $branchId);
        });
    }
}
```

---

## 10. Media & Livestream Architecture

### 10.1 Media Storage Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MEDIA & LIVESTREAM ARCHITECTURE                     │
│                                                                           │
│   ┌────────────────────────────────────────────────────────────────┐     │
│   │                    MEDIA UPLOAD PIPELINE                        │     │
│   │                                                                  │     │
│   │  Upload ──► Validation ──► Storage ──► Processing ──► CDN       │     │
│   │   │           │             │            │           │          │     │
│   │   │           ├── File type  ├── S3/MinIO ─┤── Resize ─┴── CloudFront │
│   │   │           ├── Size limit │            ├── Optimize         │     │
│   │   │           ├── Virus scan │            ├── Watermark        │     │
│   │   │           └── Checksum   │            └── Thumbnail        │     │
│   │   └── Presigned URL         │                                   │     │
│   │                             └── Multi-region replication        │     │
│   └────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│   ┌────────────────────────────────────────────────────────────────┐     │
│   │                    IMAGE OPTIMIZATION                           │     │
│   │                                                                  │     │
│   │  Original (4000x3000, 12MB)                                     │     │
│   │    ├── xs (150x150, 15KB) - Avatar thumbnails                   │     │
│   │    ├── sm (320x240, 30KB) - Mobile list                         │     │
│   │    ├── md (768x576, 80KB) - Tablet grid                         │     │
│   │    ├── lg (1024x768, 150KB) - Desktop gallery                   │     │
│   │    └── xl (1920x1440, 300KB) - Hero banners                    │     │
│   │                                                                  │     │
│   │  Formats: WebP (primary), AVIF (fallback), JPEG (legacy)       │     │
│   │  Quality: 85% (default), 70% (compressed), 95% (premium)       │     │
│   └────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│   ┌────────────────────────────────────────────────────────────────┐     │
│   │                    VIDEO PROCESSING                             │     │
│   │                                                                  │     │
│   │  Upload ──► Transcode ──► Adaptive Bitrate ──► HLS Delivery    │     │
│   │              │            │                       │             │     │
│   │              ├── 240p    ├── 500kbps              ├── .m3u8    │     │
│   │              ├── 480p    ├── 1500kbps             ├── .ts       │     │
│   │              ├── 720p    └── 3000kbps             └── CDN       │     │
│   │              └── 1080p                                          │     │
│   └────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.2 Media Upload Flow

```
┌────────────┐    ┌────────────┐    ┌────────────┐    ┌────────────┐
│  Client    │    │  Laravel   │    │  Storage   │    │  Queue     │
│  Upload    │    │  Backend   │    │  (S3)      │    │  Worker    │
└─────┬──────┘    └─────┬──────┘    └─────┬──────┘    └─────┬──────┘
      │                  │                  │                  │
      │ 1. Request       │                  │                  │
      │ Presigned URL    │                  │                  │
      │─────────────────►│                  │                  │
      │                  │ 2. Generate      │                  │
      │                  │ Presigned URL    │                  │
      │                  │ (10min expiry)   │                  │
      │                  │─────────────────►│                  │
      │ 3. Presigned URL │                  │                  │
      │◄─────────────────┤◄─────────────────┤                  │
      │                  │                  │                  │
      │ 4. Direct Upload │                  │                  │
      │────────────────────────────────────►│                  │
      │ 5. Success       │                  │                  │
      │◄────────────────────────────────────┤                  │
      │                  │                  │                  │
      │ 6. Confirm       │                  │                  │
      │ (File ID, hash)  │                  │                  │
      │─────────────────►│                  │                  │
      │                  │ 7. Save metadata │                  │
      │                  │ (media_files DB) │                  │
      │                  │                  │                  │
      │                  │ 8. Dispatch      │                  │
      │                  │ ProcessMediaJob  │─────────────────►│
      │                  │                  │                  │
      │                  │                  │                  │ 9. Resize
      │                  │                  │                  │ 10. Optimize
      │                  │                  │                  │ 11. Generate
      │                  │                  │                  │     Thumbnails
      │                  │                  │                  │ 12. Virus Scan
      │                  │                  │                  │
      │                  │                  │◄─────────────────┤
      │                  │                  │ (Optimized files)
      │                  │                  │                  │
      │                  │ 13. Webhook      │                  │
      │                  │ (Processing done)│                  │
      │◄─────────────────┤                  │                  │
      │                  │                  │                  │
```

### 10.3 Livestream Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      LIVESTREAM INFRASTRUCTURE                           │
│                                                                           │
│   ┌──────────┐    ┌──────────────────┐    ┌──────────────────┐         │
│   │  Camera  │───►│  RTMP Ingest     │───►│  Transcoder      │         │
│   │  (Court) │    │  (nginx-rtmp)    │    │  (FFmpeg)        │         │
│   └──────────┘    └──────────────────┘    └──────────────────┘         │
│                                                   │                     │
│                                                   ▼                     │
│   ┌──────────┐    ┌──────────────────┐    ┌──────────────────┐         │
│   │  Viewer  │◄───│  HLS CDN         │◄───│  Origin Server   │         │
│   │ (Player) │    │  (CloudFront)    │    │  (HLS Segments)  │         │
│   └──────────┘    └──────────────────┘    └──────────────────┘         │
│                                                                           │
│   Features:                                                              │
│   - Real-time score overlay                                             │
│   - Chat integration (WebSocket)                                        │
│   - Multi-camera switching                                              │
│   - Recording to VOD archive                                            │
│   - Time-shifted viewing                                                │
│   - Low-latency mode (LL-HLS)                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

### 10.4 Media File Model

```php
// app/Models/MediaFile.php
class MediaFile extends Model
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'user_id',
        'file_name', 'file_path', 'file_type', 'file_size',
        'mime_type', 'extension', 'alt_text',
        'width', 'height', 'duration', // For video
        'checksum', 'disk',
        'variants' => 'json',  // {xs, sm, md, lg, xl}
        'meta' => 'json',      // {exif, iptc, xmp}
        'is_active', 'status',
    ];

    public function getUrlAttribute(): string
    {
        return match($this->disk) {
            's3' => Storage::disk('s3')->url($this->file_path),
            'minio' => Storage::disk('minio')->url($this->file_path),
            default => asset("uploads/{$this->file_path}"),
        };
    }

    public function getVariantUrl(string $size): string
    {
        $variants = $this->variants ?? [];
        $variantPath = $variants[$size] ?? $this->file_path;
        return Storage::disk($this->disk)->url($variantPath);
    }

    public function scopeImages($query)
    {
        return $query->whereIn('mime_type', [
            'image/jpeg', 'image/png', 'image/webp', 'image/avif'
        ]);
    }

    public function scopeVideos($query)
    {
        return $query->whereIn('mime_type', [
            'video/mp4', 'video/webm', 'video/ogg'
        ]);
    }
}
```

### 10.5 CDN & Cache Strategy

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
        'visibility' => 'public',
        'cdn' => env('AWS_CDN_URL'),  // CloudFront URL
    ],

    'minio' => [
        'driver' => 's3',
        'key' => env('MINIO_ACCESS_KEY'),
        'secret' => env('MINIO_SECRET_KEY'),
        'region' => 'us-east-1',
        'bucket' => env('MINIO_BUCKET', 'pickball'),
        'url' => env('MINIO_URL', 'http://localhost:9000'),
        'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
        'use_path_style_endpoint' => true,
        'visibility' => 'public',
    ],
],

// CDN Cache Strategy
'cdn' => [
    'images' => [
        'path' => 'images/*',
        'ttl' => 31536000,  // 1 year
        'query_string' => ['v', 'size', 'format'],
        'compress' => true,
    ],
    'videos' => [
        'path' => 'videos/*',
        'ttl' => 2592000,   // 30 days
        'compress' => true,
    ],
    'documents' => [
        'path' => 'documents/*',
        'ttl' => 604800,    // 7 days
        'compress' => false,
    ],
    'uploads' => [
        'path' => 'uploads/*',
        'ttl' => 3600,      // 1 hour (temp files)
        'compress' => true,
    ],
],
```

---

## 11. Module Dependency Map

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         MODULE DEPENDENCY GRAPH                          │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    CORE MODULE (Level 0)                         │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Tenant   │  │ Auth     │  │ RBAC    │  │ Multi-lang   │   │     │
│  │  │ Mgmt     │  │ Mgmt     │  │         │  │              │   │     │
│  │  └────┬─────┘  └────┬─────┘  └────┬─────┘  └──────┬───────┘   │     │
│  └───────┼─────────────┼─────────────┼───────────────┼───────────┘     │
│          │             │             │               │                  │
│  ┌───────▼─────────────▼─────────────▼───────────────▼───────────┐     │
│  │                    COMMON MODULE (Level 1)                     │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Audit    │  │ Media    │  │ Setting  │  │ Notification  │   │     │
│  │  │ Log      │  │ Storage  │  │ Config   │  │ Engine        │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    FACILITY MODULE (Level 2)                     │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Branch   │  │ Court    │  │ Court    │  │ Price        │   │     │
│  │  │ Mgmt     │  │ Mgmt     │  │ Schedule │  │ Tier         │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  │  ┌──────────┐  ┌──────────┐                                    │     │
│  │  │ Opening  │  │ Mainten. │                                    │     │
│  │  │ Hours    │  │          │                                    │     │
│  │  └──────────┘  └──────────┘                                    │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                            │                                              │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    BOOKING MODULE (Level 3)                      │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Booking  │  │ Check-in │  │ QR Code  │  │ Waitlist     │   │     │
│  │  │ Engine   │  │ Mgmt     │  │ Mgmt     │  │              │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐                      │     │
│  │  │ Recurring│  │ Rating   │  │ Coupon   │                      │     │
│  │  │ Booking  │  │ Review   │  │ Mgmt     │                      │     │
│  │  └──────────┘  └──────────┘  └──────────┘                      │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                            │                                              │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    PAYMENT MODULE (Level 4)                      │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Payment  │  │ Wallet   │  │ Invoice  │  │ Refund       │   │     │
│  │  │ Gateway  │  │ Mgmt     │  │ Mgmt     │  │ Engine       │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                            │                                              │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    MEMBERSHIP MODULE (Level 4)                   │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Package  │  │ Member   │  │ Benefit  │  │ Referral     │   │     │
│  │  │ Mgmt     │  │ Lifecycle│  │ Mgmt     │  │ System       │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                            │                                              │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    TOURNAMENT MODULE (Level 5)                   │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ Tourn.   │  │ Bracket  │  │ Match    │  │ Referee      │   │     │
│  │  │ Engine   │  │ Generator│  │ Mgmt     │  │ Assignment   │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                            │                                              │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    ADVANCED MODULES (Level 6)                    │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │     │
│  │  │ POS      │  │ AI       │  │ Live     │  │ Community    │   │     │
│  │  │ Module   │  │ Scheduler│  │ Stream   │  │ Module       │   │     │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘   │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  Legend:  Module A ──► Module B  =  A depends on B                      │
│           Module A ──── Module B  =  A communicates with B via events   │
└─────────────────────────────────────────────────────────────────────────┘
```

### 11.1 Module Dependency Rules

```yaml
dependency_rules:
  # Level 1 modules depend only on Core
  audit_log:
    depends_on: [tenant, auth]
    events_emits: [audit.logged]

  media_storage:
    depends_on: [tenant, auth]
    events_emits: [media.uploaded, media.processed, media.deleted]

  settings:
    depends_on: [tenant]
    events_emits: [setting.updated]

  notification:
    depends_on: [tenant, auth]
    events_listens: [booking.*, payment.*, membership.*, tournament.*]
    queues: [notifications]

  # Level 3 Booking depends on Facility + Common
  booking:
    depends_on: [branch, court, price_tier, audit_log, notification]
    events_emits: [booking.*]
    queues: [high, notifications]
    transaction_safe: true

  # Level 5 Tournament depends on Booking + Player
  tournament:
    depends_on: [booking, player, court, notification]
    events_emits: [tournament.*]
    features: [ai_scheduling, live_stream]

  # No circular dependencies allowed
  constraints:
    - core modules cannot depend on domain modules
    - lower level modules cannot depend on higher level modules
    - cross-module communication must use events (not direct calls)
```

---

## 12. Deployment Strategy

### 12.1 Environment Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DEPLOYMENT ENVIRONMENTS                               │
│                                                                           │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │  Development  │    │  Staging     │    │  Production  │              │
│  │  dev.pickball │    │  staging.pick│    │  pickball.com│              │
│  │  .internal    │    │  ball.com    │    │              │              │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘              │
│         │                   │                   │                        │
│         │ Docker Compose    │ Docker Compose    │ Kubernetes (EKS)      │
│         │ - 1 app server    │ - 2 app servers   │ - Auto-scaling        │
│         │ - 1 database      │ - 1 database      │ - Multi-AZ            │
│         │ - 1 redis         │ - 1 redis         │ - Blue/Green deploy   │
│         │ - 1 queue worker  │ - 2 queue workers │ - Rolling updates     │
│         │                   │ - AI microservice │ - Canary releases     │
│         │                   │                   │                        │
│  ┌──────┴───────┐    ┌──────┴───────┐    ┌──────┴───────┐              │
│  │ Git Branch:  │    │ Git Branch:  │    │ Git Branch:  │              │
│  │ develop      │    │ staging      │    │ main         │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
└─────────────────────────────────────────────────────────────────────────┘
```

### 12.2 CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy Pickleball System

on:
  push:
    branches: [main, staging, develop]
  pull_request:
    branches: [main]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: PHP Static Analysis
        run: |
          composer install --no-interaction --prefer-dist
          ./vendor/bin/phpstan analyse --level=max app/
          ./vendor/bin/pint --test
          ./vendor/bin/phpinsights

      - name: JavaScript Quality
        run: |
          npm ci
          npm run lint
          npm run type-check

  test:
    needs: quality
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: pickball_test
          POSTGRES_USER: test
          POSTGRES_PASSWORD: test
      redis:
        image: redis:7
    steps:
      - uses: actions/checkout@v3
      - name: Run Tests
        run: |
          cp .env.testing .env
          php artisan key:generate
          php artisan migrate:fresh --seed
          php artisan test --parallel --coverage --min=80

      - name: E2E Tests
        run: |
          npm ci
          npx playwright install
          npx playwright test

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Build Docker Images
        run: |
          docker build -t pickball-app:${{ github.sha }} .
          docker build -t pickball-worker:${{ github.sha }} -f Dockerfile.worker .
          docker build -t pickball-ai:${{ github.sha }} -f ai-service/Dockerfile .

      - name: Push to Registry
        run: |
          docker tag pickball-app:${{ github.sha }} ${{ secrets.ECR }}/pickball-app:${{ github.sha }}
          docker tag pickball-app:${{ github.sha }} ${{ secrets.ECR }}/pickball-app:latest
          docker push ${{ secrets.ECR }}/pickball-app:${{ github.sha }}
          docker push ${{ secrets.ECR }}/pickball-app:latest
          # Push worker and AI images similarly

  deploy:
    needs: build
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to EKS
        run: |
          aws eks update-kubeconfig --region ap-southeast-1 --name pickball-prod
          kubectl set image deployment/app app=${{ secrets.ECR }}/pickball-app:${{ github.sha }}
          kubectl rollout status deployment/app

      - name: Run Migrations
        run: |
          kubectl exec deployment/app -- php artisan migrate --force

      - name: Clear Cache
        run: |
          kubectl exec deployment/app -- php artisan optimize:clear
          kubectl exec deployment/app -- php artisan optimize

      - name: Health Check
        run: |
          curl -f https://api.pickball.com/health || exit 1
```

### 12.3 Docker Configuration

```dockerfile
# Dockerfile (Application)
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql zip bcmath pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Build frontend
RUN npm ci && npm run build

# Production stage
FROM base AS production
COPY --from=base /var/www /var/www

EXPOSE 80 443

# Supervisor for multiple processes
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Worker specific
FROM base AS worker
CMD ["php", "artisan", "queue:work", "redis", "--sleep=3", "--tries=3"]

# AI Service
FROM python:3.11-slim AS ai
WORKDIR /ai-service
COPY ai-service/ .
RUN pip install --no-cache-dir -r requirements.txt
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
```

### 12.4 Kubernetes Configuration

```yaml
# k8s/app-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: pickball-app
  namespace: pickball
spec:
  replicas: 3
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 0
  selector:
    matchLabels:
      app: pickball-app
  template:
    metadata:
      labels:
        app: pickball-app
    spec:
      containers:
        - name: app
          image: ${ECR}/pickball-app:latest
          ports:
            - containerPort: 80
          env:
            - name: APP_ENV
              value: "production"
            - name: DB_HOST
              valueFrom:
                secretKeyRef:
                  name: db-secret
                  key: host
            - name: REDIS_HOST
              value: "redis-service"
          resources:
            requests:
              memory: "512Mi"
              cpu: "500m"
            limits:
              memory: "1Gi"
              cpu: "1000m"
          livenessProbe:
            httpGet:
              path: /health
              port: 80
            initialDelaySeconds: 30
            periodSeconds: 10
          readinessProbe:
            httpGet:
              path: /health/ready
              port: 80
            initialDelaySeconds: 5
            periodSeconds: 5

---
# Horizontal Pod Autoscaler
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: pickball-app-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: pickball-app
  minReplicas: 3
  maxReplicas: 20
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
    - type: Resource
      resource:
        name: memory
        target:
          type: Utilization
          averageUtilization: 80
    - type: Pods
      pods:
        metric:
          name: laravel_queue_size
        target:
          type: AverageValue
          averageValue: 50

---
# Worker Deployment
apiVersion: apps/v1
kind: Deployment
metadata:
  name: pickball-worker
  namespace: pickball
spec:
  replicas: 4
  selector:
    matchLabels:
      app: pickball-worker
  template:
    spec:
      containers:
        - name: worker
          image: ${ECR}/pickball-worker:latest
          env:
            - name: QUEUE_WORKER
              value: "true"
          resources:
            requests:
              memory: "256Mi"
              cpu: "250m"
---
# Redis Deployment
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: redis
spec:
  serviceName: redis-service
  replicas: 3
  selector:
    matchLabels:
      app: redis
  template:
    spec:
      containers:
        - name: redis
          image: redis:7-alpine
          ports:
            - containerPort: 6379
          volumeMounts:
            - name: redis-data
              mountPath: /data
  volumeClaimTemplates:
    - metadata:
        name: redis-data
      spec:
        accessModes: ["ReadWriteOnce"]
        resources:
          requests:
            storage: 10Gi
```

---

## 13. CDN Strategy

### 13.1 CDN Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      CDN ARCHITECTURE                                    │
│                                                                           │
│  Global CDN (CloudFront / Cloudflare)                                    │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  Edge Locations (50+ globally)                                   │     │
│  │                                                                  │     │
│  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐       │     │
│  │  │ US     │ │ Europe │ │ Asia   │ │ Oceania│ │ S.Am   │       │     │
│  │  │ (6)    │ │ (8)    │ │ (12)   │ │ (3)    │ │ (4)    │       │     │
│  │  └────────┘ └────────┘ └────────┘ └────────┘ └────────┘       │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │  Static      │    │  Dynamic     │    │  API Cache   │              │
│  │  Assets      │    │  Content     │    │  (Optional)  │              │
│  │              │    │              │    │              │              │
│  │  TTL: 1 year │    │  TTL: 1 hour │    │  TTL: 0-60s  │              │
│  │  /css/*      │    │  /images/*   │    │  /api/*      │              │
│  │  /js/*       │    │  /uploads/*  │    │              │              │
│  │  /fonts/*    │    │  /videos/*   │    │              │              │
│  │  /icons/*    │    │  /avatars/*  │    │              │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
└─────────────────────────────────────────────────────────────────────────┘
```

### 13.2 Cache Invalidation Strategy

```php
// app/Services/CDNService.php
class CDNService {
    protected string $distributionId;

    public function invalidateByTag(string $tag): void
    {
        // Invalidate by cache tag
        $paths = match($tag) {
            'tenant.images' => ["/images/tenant/{$this->tenantId}/*"],
            'courts' => ["/images/courts/{$this->tenantId}/*"],
            'avatars' => ["/avatars/*"],
            default => ["/$tag/*"],
        };

        $this->createInvalidation($paths);
    }

    public function warmCache(string $url): void
    {
        // Pre-warm cache by requesting from multiple edge locations
        dispatch(new WarmCDNJob($url));
    }

    protected function createInvalidation(array $paths): void
    {
        CloudFrontClient::createInvalidation([
            'DistributionId' => $this->distributionId,
            'InvalidationBatch' => [
                'Paths' => [
                    'Quantity' => count($paths),
                    'Items' => $paths,
                ],
                'CallerReference' => (string) Str::uuid(),
            ],
        ]);
    }
}
```

---

## 14. Backup Strategy

### 14.1 Backup Schedule

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          BACKUP STRATEGY                                 │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    DATABASE BACKUPS                              │     │
│  │                                                                  │     │
│  │  Type    │ Frequency │ Retention │ Method         │ Storage    │     │
│  │  ────────┼───────────┼───────────┼────────────────┼─────────── │     │
│  │  Full    │ Daily     │ 30 days   │ pg_dump + gzip │ S3 Glacier │     │
│  │  Weekly  │ Every Sun │ 12 weeks  │ pg_dump        │ S3 Glacier │     │
│  │  Monthly │ 1st of mo │ 12 months │ pg_dump        │ S3 Glacier │     │
│  │  WAL     │ Contin.   │ 7 days    │ WAL archiving  │ S3 Standard│     │
│  │                                                                  │     │
│  │  Point-in-Time Recovery (PITR): Up to 7 days                     │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    APPLICATION BACKUPS                           │     │
│  │                                                                  │     │
│  │  Type      │ Frequency │ Retention │ Location                    │     │
│  │  ──────────┼───────────┼───────────┼─────────────────────────────│     │
│  │  Code      │ Per deploy│ Indefinite│ GitHub + S3 (releases)     │     │
│  │  Config    │ Per change│ 30 days   │ S3 + Vault                 │     │
│  │  Uploads   │ Daily     │ 30 days   │ S3 Cross-region            │     │
│  │  Logs      │ Daily     │ 90 days   │ S3 + Elasticsearch         │     │
│  │  Redis     │ Hourly    │ 24 hours  │ RDB snapshots to S3        │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    DISASTER RECOVERY                             │     │
│  │                                                                  │     │
│  │  RPO (Recovery Point Objective): 1 hour                         │     │
│  │  RTO (Recovery Time Objective):  4 hours                        │     │
│  │                                                                  │     │
│  │  Multi-region: Primary (ap-southeast-1) + DR (ap-northeast-1)   │     │
│  │  Active-Passive: DR region on standby, auto-failover            │     │
│  │  DR Testing: Quarterly drills                                   │     │
│  └─────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 14.2 Backup Script

```bash
#!/bin/bash
# scripts/backup.sh - Database Backup Script

BACKUP_DIR="/backups/$(date +%Y-%m-%d)"
DB_NAME="pickball_db"
S3_BUCKET="s3://pickball-backups"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Full database backup with compression
echo "Starting PostgreSQL backup..."
pg_dump \
    --host="$DB_HOST" \
    --port=5432 \
    --username=postgres \
    --dbname="$DB_NAME" \
    --format=custom \
    --verbose \
    --file="$BACKUP_DIR/${DB_NAME}_$(date +%H%M%S).dump" \
    --exclude-table-data='audit_logs' \
    --exclude-table-data='booking_logs'

# Compress
gzip "$BACKUP_DIR/${DB_NAME}_*.dump"

# Upload to S3
aws s3 cp "$BACKUP_DIR/" "$S3_BUCKET/database/" --recursive

# Cleanup local files older than 7 days
find /backups/ -type f -mtime +7 -delete

# Notify
if [ $? -eq 0 ]; then
    echo "Backup completed successfully"
else
    echo "Backup failed!" | mail -s "Backup Failure Alert" admin@pickball.com
fi
```

---

## 15. Scaling Strategy

### 15.1 Scaling Matrix

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          SCALING STRATEGY                                │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    HORIZONTAL SCALING                            │     │
│  │                                                                  │     │
│  │  Component   │ Metric             │ Auto-scale │ Max replicas   │     │
│  │  ────────────┼────────────────────┼────────────┼────────────────│     │
│  │  Web Server  │ CPU > 70%         │ HPA        │ 20             │     │
│  │  Queue       │ Queue depth > 50  │ HPA        │ 10             │     │
│  │  AI Service  │ CPU > 60%         │ HPA        │ 5              │     │
│  │  Reverb WS   │ Connections > 1000│ HPA        │ 10             │     │
│  │                                                                  │     │
│  │  Scaling Rules:                                                  │     │
│  │  - Scale up: if metric > threshold for 3 minutes                │     │
│  │  - Scale down: if metric < threshold for 10 minutes             │     │
│  │  - Cooldown: 3 minutes between scaling events                   │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    VERTICAL SCALING (Database)                   │     │
│  │                                                                  │     │
│  │  Tier   │ vCPUs │ RAM    │ Storage    │ Connections │ Cost      │     │
│  │  ───────┼───────┼────────┼────────────┼─────────────┼────────── │     │
│  │  Small  │ 2     │ 8GB    │ 100GB SSD  │ 100         │ $50/mo   │     │
│  │  Medium │ 4     │ 16GB   │ 500GB SSD  │ 250         │ $150/mo  │     │
│  │  Large  │ 8     │ 32GB   │ 1TB NVMe   │ 500         │ $400/mo  │     │
│  │  XL     │ 16    │ 64GB   │ 2TB NVMe   │ 1000        │ $1000/mo │     │
│  │  2XL    │ 32    │ 128GB  │ 4TB NVMe   │ 2000        │ $2500/mo │     │
│  │                                                                  │     │
│  │  Read Replicas: 0-3 based on read/write ratio                   │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │                    CACHING STRATEGY                              │     │
│  │                                                                  │     │
│  │  Level   │ Technology   │ Data                 │ TTL            │     │
│  │  ────────┼──────────────┼──────────────────────┼────────────────│     │
│  │  L1      │ Application  │ Hot data             │ Seconds        │     │
│  │  L2      │ Redis        │ Frequently accessed  │ Minutes        │     │
│  │  L3      │ Database     │ Computed queries     │ Hours          │     │
│  │  L4      │ CDN          │ Static assets        │ Months/Years  │     │
│  │                                                                  │     │
│  │  Cache-Aside Pattern:                                            │     │
│  │  1. Check cache (Redis)                                          │     │
│  │  2. If miss, query DB                                            │     │
│  │  3. Store in cache                                                │     │
│  │  4. Return result                                                │     │
│  │                                                                  │     │
│  │  Cache Invalidation:                                             │     │
│  │  - Tag-based: court:123, booking:456, tenant:789                │     │
│  │  - Write-through: Update cache on every write                   │     │
│  │  - Lazy: Cache invalidation + lazy load                         │     │
│  └─────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 15.2 Database Read/Write Splitting

```php
// config/database.php
'connections' => [
    'pgsql' => [
        'write' => [
            'host' => env('DB_HOST_WRITE', 'localhost'),
        ],
        'read' => [
            'host' => [
                env('DB_HOST_READ_1', 'localhost'),
                env('DB_HOST_READ_2', 'localhost'),
            ],
        ],
        'sticky' => true,
        'driver' => 'pgsql',
        'database' => env('DB_DATABASE', 'pickball'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
    ],
],

// Read/write splitting with sticky sessions
class DatabaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        DB::whenQueryingForLongerThan(500, function ($connection, $query) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);

            // Auto-scale read replicas if slow queries persist
            if ($query->time > 2000) {
                $this->scaleUpReadReplicas();
            }
        });
    }
}
```

### 15.3 Queue Scaling

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default'],
            'balance' => 'auto',
            'minProcesses' => 3,
            'maxProcesses' => 20,
            'balanceMaxShift' => 2,
            'balanceCooldown' => 3,
            'tries' => 3,
        ],
        'supervisor-2' => [
            'connection' => 'redis',
            'queue' => ['notifications', 'events'],
            'balance' => 'auto',
            'minProcesses' => 3,
            'maxProcesses' => 15,
            'tries' => 3,
        ],
        'supervisor-3' => [
            'connection' => 'redis',
            'queue' => ['media', 'webhooks'],
            'balance' => 'auto',
            'minProcesses' => 2,
            'maxProcesses' => 10,
            'tries' => 3,
        ],
        'supervisor-4' => [
            'connection' => 'redis',
            'queue' => ['low', 'ai'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'tries' => 1,
        ],
    ],
],
```

---

## 16. Security Strategy

### 16.1 Security Layers

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        SECURITY ARCHITECTURE                             │
│                                                                           │
│  Layer 1: Network Security                                               │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  - VPC with public/private subnets                              │     │
│  │  - WAF (AWS WAF / Cloudflare)                                   │     │
│  │  - DDoS Protection (AWS Shield)                                 │     │
│  │  - Security Groups (least privilege)                            │     │
│  │  - NACLs (network ACLs)                                         │     │
│  │  - API Gateway rate limiting (1000 req/s per tenant)            │     │
│  │  - IP whitelisting for admin panel                              │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  Layer 2: Authentication & Authorization                                │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  - JWT-based authentication (access + refresh tokens)           │     │
│  │  - Access token: 15 min expiry, Refresh token: 7 days          │     │
│  │  - MFA for admin accounts (TOTP)                                │     │
│  │  - OAuth 2.0 / Social login (Google, Facebook, Zalo)           │     │
│  │  - Role-Based Access Control (RBAC) with 5 default roles       │     │
│  │  - Permission-based authorization (CRUD per module)             │     │
│  │  - API token scopes (read/write/admin)                          │     │
│  │  - Session management with device fingerprinting                │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  Layer 3: Application Security                                          │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  - SQL injection prevention (parameterized queries)             │     │
│  │  - XSS prevention (Content-Security-Policy headers)            │     │
│  │  - CSRF protection (double-submit cookie pattern)              │     │
│  │  - Input validation (server + client side)                     │     │
│  │  - Output encoding (blade + API responses)                     │     │
│  │  - Rate limiting per user/IP/tenant                            │     │
│  │  - File upload validation (type, size, virus scan)             │     │
│  │  - CORS configuration (whitelist origins)                      │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  Layer 4: Data Security                                                 │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  - Encryption at rest (AES-256)                                │     │
│  │  - Encryption in transit (TLS 1.3)                             │     │
│  │  - PII data encryption (customer name, phone, email)           │     │
│  │  - Password hashing (bcrypt with 12 rounds)                    │     │
│  │  - Audit logging (all CRUD operations)                         │     │
│  │  - Database backup encryption                                  │     │
│  │  - Soft delete (data retention policy)                         │     │
│  │  - Data anonymization for analytics                            │     │
│  └─────────────────────────────────────────────────────────────────┘     │
│                                                                           │
│  Layer 5: Operational Security                                          │
│  ┌─────────────────────────────────────────────────────────────────┐     │
│  │  - Secrets management (HashiCorp Vault / AWS Secrets Manager)  │     │
│  │  - Environment isolation (dev/staging/prod)                    │     │
│  │  - Container scanning (Trivy on every build)                   │     │
│  │  - Dependency scanning (Dependabot + Snyk)                     │     │
│  │  - SAST/DAST scanning (SonarQube + OWASP ZAP)                 │     │
│  │  - Incident response plan                                      │     │
│  │  - Security headers (HSTS, X-Frame-Options, etc.)              │     │
│  │  - Regular penetration testing (quarterly)                     │     │
│  └─────────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 16.2 RBAC Implementation

```php
// app/Services/PermissionGate.php
class PermissionGate
{
    // Permission hierarchy
    const PERMISSIONS = [
        'courts' => ['view', 'create', 'edit', 'delete', 'manage_maintenance'],
        'bookings' => ['view', 'create', 'edit', 'cancel', 'reschedule', 'checkin'],
        'players' => ['view', 'create', 'edit', 'delete', 'manage_wallet', 'manage_membership'],
        'users' => ['view', 'create', 'edit', 'delete', 'assign_roles'],
        'roles' => ['view', 'create', 'edit', 'delete', 'assign_permissions'],
        'settings' => ['view', 'edit'],
        'reports' => ['view', 'export'],
        'payments' => ['view', 'process', 'refund'],
        'tournaments' => ['view', 'create', 'edit', 'delete', 'manage_matches'],
        'media' => ['upload', 'delete', 'manage'],
        'audit' => ['view', 'export'],
        'branches' => ['view', 'create', 'edit', 'delete'],
        'community' => ['moderate', 'delete'],
    ];

    // Default roles with their permissions
    const DEFAULT_ROLES = [
        'superadmin' => 'all',  // Bypasses all permission checks
        'owner' => ['*'],       // All permissions for their tenant
        'manager' => [
            'courts.*', 'bookings.*', 'players.view', 'players.edit',
            'reports.*', 'payments.view', 'settings.edit',
        ],
        'staff' => [
            'courts.view', 'bookings.*', 'players.view',
            'payments.view', 'checkin',
        ],
        'referee' => [
            'bookings.view', 'tournaments.view', 'tournaments.manage_matches',
        ],
        'player' => [
            'bookings.view', 'bookings.create', 'bookings.cancel',
            'profile.edit',
        ],
    ];
}

// Tenant-level permission check
class TenantPermissionMiddleware
{
    public function handle($request, Closure $next, ...$permissions)
    {
        $user = auth()->user();

        // Superadmin bypass
        if ($user->is_superadmin) {
            return $next($request);
        }

        // Check if user belongs to the requested tenant
        if ($user->tenant_id !== tenant()->id) {
            abort(403, 'Unauthorized tenant access');
        }

        // Check specific permission
        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }

        return $next($request);
    }
}
```

### 16.3 Audit Log Implementation

```php
// app/Traits/Auditable.php
trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLogService::log('created', $model);
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            if (!empty($changes)) {
                AuditLogService::log('updated', $model);
            }
        });

        static::deleted(function ($model) {
            AuditLogService::log('deleted', $model);
        });
    }
}

// app/Services/AuditLogService.php
class AuditLogService
{
    public static function log(string $action, $model): void
    {
        $table = $model->getTable();
        $oldValues = $model->getOriginal();
        $newValues = $model->getAttributes();

        // Don't log sensitive fields
        $excluded = ['password', 'remember_token', 'two_factor_secret'];
        $oldValues = array_diff_key($oldValues, array_flip($excluded));
        $newValues = array_diff_key($newValues, array_flip($excluded));

        AuditLog::create([
            'tenant_id' => tenant()?->id,
            'branch_id' => branch()?->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => class_basename($model),
            'table_name' => $table,
            'record_id' => $model->id,
            'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
            'new_values' => !empty($newValues) ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => self::generateDescription($action, $model),
        ]);

        // Async archive if table grows too fast
        if (rand(1, 100) === 1) {  // 1% chance
            dispatch(new ArchiveOldAuditLogsJob());
        }
    }

    public static function generateDescription(string $action, $model): string
    {
        $modelName = class_basename($model);
        $identifier = $model->name ?? $model->code ?? $model->id;

        return match($action) {
            'created' => "Tạo {$modelName}: {$identifier}",
            'updated' => "Cập nhật {$modelName}: {$identifier}",
            'deleted' => "Xóa {$modelName}: {$identifier}",
            default => "{$action} {$modelName}: {$identifier}",
        };
    }
}
```

### 16.4 Security Headers

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' https://cdn.jsdelivr.net; " .
               "connect-src 'self' https://api.pickball.com wss://reverb.pickball.com; " .
               "frame-src 'self' https://www.google.com; " .
               "media-src 'self' https://cdn.pickball.com blob:;";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
```

### 16.5 Data Encryption Strategy

```php
// app/Services/EncryptionService.php
class EncryptionService
{
    // PII data encryption at application level
    public function encryptPII(string $value): string
    {
        return encrypt($value);  // Laravel's built-in AES-256-CBC
    }

    public function decryptPII(string $encrypted): string
    {
        return decrypt($encrypted);
    }

    // Database column encryption (partial)
    public function encryptPhone(string $phone): string
    {
        $prefix = substr($phone, 0, 4);  // Store first 4 digits + encrypted
        $encrypted = encrypt($phone);
        return "{$prefix}:{$encrypted}";
    }

    // Token generation for API access
    public function generateApiToken(User $user): string
    {
        $token = Str::random(64);

        // Hash before storing
        ApiToken::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addYear(),
            'abilities' => ['read', 'write'],
        ]);

        return $token;  // Return unhashed only once
    }
}
```

### 16.6 Compliance & Data Retention

```php
// config/compliance.php
return [
    'data_retention' => [
        'audit_logs' => [
            'retention_days' => 365,  // 1 year
            'archive_after_days' => 90,
            'delete_after_days' => 365,
        ],
        'booking_history' => [
            'retention_days' => 730,  // 2 years
            'archive_after_days' => 365,
            'anonymize_after_days' => 730,  // Remove PII
        ],
        'payment_records' => [
            'retention_days' => 1825,  // 5 years (legal requirement)
            'archive_after_days' => 365,
        ],
        'user_sessions' => [
            'retention_days' => 90,
            'delete_after_days' => 90,
        ],
        'notification_logs' => [
            'retention_days' => 180,
            'delete_after_days' => 180,
        ],
    ],

    'gdpr_compliance' => [
        'right_to_access' => true,
        'right_to_erasure' => true,
        'data_portability' => true,
        'consent_records' => true,
    ],

    'pci_dss' => [
        'card_data_never_stored' => true,
        'use_tokenization' => true,
        'encrypt_pan' => true,
    ],
];
```

---

## Appendix A: Code Standards & Patterns

### A.1 Directory Structure

```
app/
├── Console/
│   └── Commands/          # Custom Artisan commands
├── Controllers/
│   ├── Admin/             # Admin panel controllers
│   ├── Api/               # API controllers
│   ├── Player/            # Player portal controllers
│   └── Webhook/           # Webhook controllers
├── Events/                # Event classes
├── Exceptions/            # Custom exception classes
├── Filters/               # Middleware/filters
├── Helpers/               # Helper functions
├── Http/
│   ├── Middleware/         # HTTP middleware
│   ├── Requests/          # Form request validation
│   └── Resources/         # API resource transformers
├── Jobs/                  # Queue job classes
├── Listeners/             # Event listeners
├── Mail/                  # Mailable classes
├── Models/                # Eloquent models
├── Notifications/         # Notification classes
├── Observers/             # Model observers
├── Repositories/          # Repository pattern
├── Rules/                 # Custom validation rules
├── Services/              # Business logic services
│   ├── AI/               # AI integration services
│   ├── Booking/          # Booking domain services
│   ├── Payment/          # Payment gateway services
│   ├── Notification/     # Multi-channel notification
│   └── Reporting/        # Report generation services
├── Traits/                # Reusable traits
└── ValueObjects/          # Domain value objects

docs/
├── ARCHITECTURE.md        # This document
├── api/                  # API documentation
├── database/             # DB schema documentation
└── deployment/           # Deployment guides

tests/
├── Unit/                 # Unit tests
├── Feature/              # Feature/integration tests
├── Api/                  # API endpoint tests
└── E2E/                  # End-to-end tests
```

### A.2 Coding Conventions

```php
// 1. Service Layer Pattern
class BookingService {
    // Public methods = use cases
    public function createBooking(array $data): Booking;
    public function cancelBooking(int $bookingId, string $reason): void;
    public function rescheduleBooking(int $bookingId, array $newTime): Booking;

    // Private methods = implementation details
    private function validateAvailability(array $data): void;
    private function calculatePricing(array $data): float;
    private function checkConflicts(array $data): void;
}

// 2. Repository Pattern
interface BookingRepositoryInterface {
    public function findById(int $id): ?Booking;
    public function findByTenant(int $tenantId, array $filters): Collection;
    public function save(Booking $booking): Booking;
    public function delete(int $id): bool;
}

class BookingRepository implements BookingRepositoryInterface {
    public function __construct(
        private BookingModel $model,
        private CacheService $cache
    ) {}

    public function findById(int $id): ?Booking {
        return $this->cache->remember(
            "booking:{$id}",
            300,
            fn() => $this->model->with(['items', 'player', 'payments'])->find($id)
        );
    }
}

// 3. Transaction Safety
class BookingService {
    public function createBooking(array $data): Booking {
        return DB::transaction(function () use ($data) {
            // Lock court slots
            $court = Court::where('id', $data['court_id'])
                ->lockForUpdate()
                ->first();

            // Create booking
            $booking = Booking::create([...]);

            // Create booking items
            foreach ($data['items'] as $item) {
                BookingItem::create([...]);
            }

            // Process payment if applicable
            if ($data['require_payment']) {
                $this->paymentService->process($booking, $data['payment']);
            }

            // Dispatch events
            EventBus::dispatch('booking.created', $booking->toArray());

            return $booking->fresh();
        }, 5);  // 5 retries for deadlock
    }
}

// 4. Queue Safety
class ProcessPaymentJob implements ShouldQueue, ShouldBeUnique {
    public $uniqueFor = 3600;
    public $backoff = [10, 30, 60];
    public $maxExceptions = 3;

    public function uniqueId(): string {
        return "payment:{$this->payment->id}";
    }

    public function handle(): void {
        // Idempotent processing
        if ($this->payment->status === 'completed') {
            return; // Already processed
        }

        // Process with gateway
        $result = PaymentGateway::charge($this->payment);

        // Handle result
        if ($result->success) {
            $this->payment->markAsCompleted($result->transaction_id);
        } else {
            throw new PaymentFailedException($result->error);
        }
    }

    public function failed(\Throwable $e): void {
        // Move to DLQ + notify
        $this->payment->markAsFailed($e->getMessage());
        NotificationService::notifyAdmin(...);
    }
}

// 5. Soft Delete
trait SoftDeleteWithAudit {
    use SoftDeletes;

    public static function bootSoftDeleteWithAudit(): void {
        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                AuditLogService::log('soft_deleted', $model);
            }
        });

        static::restoring(function ($model) {
            AuditLogService::log('restored', $model);
        });
    }
}
```

### A.3 Service Layer Organization

```php
// app/Providers/AppServiceProvider.php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(CourtRepositoryInterface::class, CourtRepository::class);
        $this->app->bind(PlayerRepositoryInterface::class, PlayerRepository::class);

        // Service bindings
        $this->app->singleton(EventBusService::class);
        $this->app->singleton(AIService::class);
        $this->app->singleton(NotificationService::class);

        // Facades
        $this->app->bind('ai', fn() => $this->app->make(AIService::class));
        $this->app->bind('notification', fn() => $this->app->make(NotificationService::class));
    }
}
```

---

## Appendix B: Performance Benchmarks

```yaml
performance_targets:
  api_response_time:
    p50: < 100ms
    p95: < 300ms
    p99: < 1000ms

  page_load_time:
    first_contentful_paint: < 1.5s
    time_to_interactive: < 3s
    largest_contentful_paint: < 2.5s

  database:
    query_time_p95: < 50ms
    connections_pool: 100
    replication_lag: < 1s

  queue:
    job_execution_time_p95: < 30s
    queue_backlog_max: 1000
    processing_rate: > 1000 jobs/min

  websocket:
    connections_per_node: 5000
    message_latency_p95: < 100ms
    message_throughput: > 10000 msg/s

  ai_service:
    schedule_optimization: < 60s
    demand_prediction: < 5s
    dynamic_pricing: < 10s

  concurrency:
    max_concurrent_users: 10000
    max_concurrent_bookings: 500
    api_requests_per_second: 2000
```

---

## Appendix C: Monitoring & Observability

```yaml
monitoring:
  apm: Laravel Pulse + Sentry

  metrics:
    - Request rate, latency, error rate
    - Queue depth, processing time
    - Database connections, query time
    - Cache hit ratio
    - Redis memory usage
    - Disk usage
    - Network throughput

  logging:
    - Centralized: ELK Stack (Elasticsearch, Logstash, Kibana)
    - Structured logging: JSON format
    - Log levels: debug, info, warning, error, critical
    - Retention: 30 days (hot), 90 days (warm), 1 year (cold)

  alerting:
    - PagerDuty / OpsGenie for critical alerts
    - Critical: Service down, DB connection failed
    - Warning: High latency, queue backlog
    - Info: Deployment, scaling events

  dashboards:
    - Grafana: System metrics
    - Laravel Pulse: Application metrics
    - Kibana: Log analysis
    - CloudWatch: AWS infrastructure
```

---

> **Document Version:** 2.0.0
> **Author:** Principal SaaS Architect
> **Reviewers:** DevOps, Security, Frontend, Backend teams
> **Approved by:** CTO
> **Next Review:** 2026-10-06
