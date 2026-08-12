# 🔍 VENUE OS AUDIT — PICKLEBALL VENUE OPERATING SYSTEM

> **Ngày audit:** 2026-08-09
> **Phạm vi:** Đánh giá toàn bộ repository để nâng cấp module CHỦ SÂN / CLUB thành **PICKLEBALL VENUE OPERATING SYSTEM**
> **Nguyên tắc:** Không tạo hệ thống độc lập. Tất cả dùng chung Customer, Player, Booking, Court, Match, Tournament, Payment, Rating, Ranking.

---

## A. EXISTING VENUE FEATURES

Hệ thống hiện tại đã rất mạnh, có sẵn nền tảng cho hầu hết các module chủ sân:

| # | Yêu cầu | Hiện trạng |
|---|---------|------------|
| 1 | Dashboard | ✅ `DashboardController` + `OperationsDashboardService` — KPI booking/doanh thu/đã thu, lịch sắp diễn ra, walk-in, waitlist, courts, coaching, competition, invoices |
| 2 | Court Occupancy | 🟡 `FacilityApi::utilization` + `peakHours` + `courtRanking` — chưa có UI admin hoàn chỉnh |
| 3 | Revenue Per Court | 🟡 `FacilityApi::revenueByCourt` — chưa có UI admin |
| 4 | Peak/Off-Peak | 🟡 `FacilityApi::peakHours` — chưa có auto-analysis + cấu hình pricing theo peak |
| 5 | Dynamic Pricing | ✅ `PricingService` + `PricingRuleModel` + conditions (branch/court_type/court/weekday/time_range/holiday/member_level) |
| 6 | Smart Price Rule | ❌ Chưa có đề xuất tăng giá theo occupancy > 80% / promotion khi < 30% |
| 7 | Multiple Pricing Plans | 🟡 Chỉ có Standard/Member pricing — thiếu VIP/Coach/Tournament/Corporate |
| 8 | Membership Management | ✅ `MembershipService` + `MembershipPackageModel` (duration_days, price, discount_percent, booking_priority) |
| 9 | Membership Entitlement | 🟡 Discount tồn tại — thiếu booking quota, free open play, auto check entitlement |
| 10 | Membership Renewal | 🟡 `MembershipService::renew()` tồn tại — thiếu dashboard expiring today/7/30 days |
| 11-15 | Open Play | ✅ Hoàn chỉnh: session, capacity, waitlist, skill range, host, rotation engine, match creation |
| 16 | Court Block Management | 🟡 Maintenance tồn tại — thiếu block types (Private Event/Tournament/Staff/Other) |
| 17-18 | Maintenance + History | ✅ `CourtMaintenanceModel` + maintenance lịch |
| 19 | Equipment Management | ❌ Chưa có (Net, Lights, Scoreboard, Ball Machine, Racket, Benches) |
| 20-21 | Inventory + Low Stock | ✅ `InventoryModel` + `InventoryMovementModel` + POS — chưa có LOW STOCK alert |
| 22 | POS LITE | ✅ `PosController` + `PosService` |
| 23 | Front Desk Mode | ❌ Chưa có màn hình quầy thống nhất |
| 24 | Quick Booking | ✅ `BookingsController::create/store` |
| 25 | Walk-in Customer | ✅ `WalkInService` + `WalkInSessionModel` |
| 26 | Check-in | ✅ QR + booking check-in |
| 27 | No-show Engine | 🟡 `no_show_count` trong customers — thiếu policy configurable + auto-actions |
| 28-29 | Waitlist + Recurring | ✅ Cả hai đều hoàn chỉnh |
| 30-31 | Corporate Booking | ❌ Chưa có corporate account/package/credit limit/billing cycle |
| 32-34 | Group/Community | 🟡 Community posts/reactions tồn tại — thiếu community với members/admin/events/calendar |
| 35-40 | Coach Management | ✅ Coaches, availability, blackouts, sessions, attendance — thiếu coach commission + player progress |
| 41-45 | League/Ladder/Club Ranking/Championship/Achievements | 🟡 Ladder/League tồn tại — thiếu club championship season + player achievements badges UI |
| 46-47 | Loyalty/Referral | 🟡 Referral tồn tại — thiếu loyalty points (booking/tournament/open play/referral → voucher/free court/merchandise) |
| 48-49 | Promotion Engine | ✅ Promotions + conditions tồn tại |
| 50-51 | CRM Campaign/Segment | ❌ Chưa có campaigns + dynamic segments |
| 52 | Automated Reminder | 🟡 Notification templates + jobs — thiếu booking reminder 24h/2h, tournament closing, membership 7 days |
| 53 | Notification Center | ✅ Hoàn chỉnh |
| 54-58 | Staff/Shift/Attendance/Tasks/Handover | ❌ Chưa có staff shift/attendance/tasks/handover |
| 59-61 | Incident/Feedback/Service Recovery | 🟡 Reviews exist — thiếu incident management + service recovery workflow |
| 62-64 | Revenue Analytics/Profitability/Daily Closing | 🟡 Operations Report tồn tại — thiếu profitability + daily closing |
| 65 | Cash Management | ❌ Chưa có opening/closing cash |
| 66-67 | Refund/Receivables | ✅ Refund tồn tại + invoice outstanding |
| 68-69 | Owner Reporting/Tax | 🟡 CSV export — thiếu PDF export + tax rate abstraction |
| 70-76 | Venue Profile/Public Venue/Reviews/Amenities/Announcement/Media | 🟡 Media library + public portal — thiếu /venues/{slug} + venue amenities + announcement |
| 77-81 | Multi-branch/Cross-branch/Owner Dashboard/Comparison | 🟡 Branch model tồn tại — thiếu multi-venue dashboard + venue comparison |
| 82-83 | Smart Insights/Demand Forecast | ❌ Chưa có |
| 84 | Booking Recommendation | ❌ Chưa có |
| 85-86 | Owner AI Assistant | 🟡 AI scheduling tồn tại — thiếu owner AI assistant + AI actions |
| 87-89 | Command Center/Alert/Task Engine | ❌ Chưa có |
| 90-92 | Mobile/Tablet/Offline | 🟡 Player mobile-first — thiếu front desk tablet + offline degraded mode |
| 93-94 | SaaS Plan/Onboarding | 🟡 SaaS plans tồn tại — thiếu owner onboarding wizard |
| 95-96 | Setup Wizard/Demo | 🟡 Demo data tồn tại — thiếu setup wizard progress |
| 97-98 | Import/Export | ❌ Chưa có Import Assistant — Export CSV tồn tại |
| 99-100 | Data Ownership/Governance | ✅ Đã có governance boundary + tenant policy |

---

## B. EXISTING CRM

| Thành phần | Hiện trạng |
|-----------|-----------|
| Bảng `customers` | ✅ Mới migration `2026-08-10-100000` — customer riêng biệt với player, có total_bookings, completed_bookings, no_show_count, total_spend |
| `customer_timeline_events` | ✅ Sự kiện booking_created, booking_backfilled |
| `customer_tags` + `customer_tag_links` | ✅ Tags cho phân loại khách |
| `CustomerService` | ✅ resolveForBooking, recordBooking kết nối booking → customer |
| Backfill migration | ✅ Chuyển lịch sử booking cũ sang customers |
| CRM Campaign | ❌ Chưa có |
| Customer Segment (dynamic) | ❌ Chưa có |
| Loyalty Points | ❌ Chưa có |

---

## C. EXISTING BOOKING

| Thành phần | Hiện trạng |
|-----------|-----------|
| `BookingService` | ✅ Create/cancel/reschedule/check-in QR |
| `BookingStateMachine` | ✅ draft → hold → pending_payment → reserved/paid → checked_in → completed |
| `BookingItemModel` | ✅ Multi-court booking |
| `BookingLogModel` | ✅ Audit booking |
| `BookingQrCodeModel` | ✅ QR check-in dùng 1 lần |
| Conflict prevention | ✅ Transaction + row lock |
| `AvailabilityService` | ✅ Weekly court slots |
| `RecurringBookingService` | ✅ Template + occurrence + pause/cancel |
| `BookingWaitlistService` | ✅ Waitlist + auto-notify + atomic claim |
| `WalkInService` | ✅ Walk-in sessions + booking link |
| No-show engine | 🟡 Có `no_show_count` — thiếu policy/auto-actions |

---

## D. EXISTING TOURNAMENT

| Thành phần | Hiện trạng |
|-----------|-----------|
| `TournamentModel` | ✅ CRUD + state flow |
| `TournamentRegistrationService` | ✅ Approve/reject/payment |
| `TournamentSchedulerService` | ✅ Auto-schedule + conflict detection |
| `TournamentBracketController` | ✅ Bracket + export |
| `TournamentOperationsService` | ✅ Control room |
| `TournamentCheckInService` | ✅ Check-in + no-show |
| `TournamentPrintCenterService` | ✅ 12 mẫu tài liệu |
| `TournamentTemplateService` | ✅ Templates |
| `TournamentMatchNetworkAdapter` | ✅ Sync unified matches |
| Score + Live Score | ✅ `ScoreService` + `LiveScoreService` |
| Competition (League/Ladder) | ✅ `CompetitionService` — round-robin/league/ladder |
| Club Championship | ❌ Chưa có season-based points |

---

## E. EXISTING PLAYER

| Thành phần | Hiện trạng |
|-----------|-----------|
| `PlayerModel` | ✅ Registry riêng + user_id link |
| `PlayerPassportService` | ✅ National Player ID + passport |
| `PlayerRatingService` | ✅ Rating theo discipline |
| `PlayerWalletModel` | ✅ Wallet + ledger |
| `PlayerMembership` | ✅ Membership per player |
| `PlayerAchievementModel` + `PlayerBadgeModel` | ✅ Đã có models |
| `PlayerSkillClaimService` | ✅ Skill claims |
| Player portal | ✅ Booking/teams/matches/open-play/social/coaching/competitions/community/livestream/growth |
| Achievements UI | ❌ Chưa có UI hiển thị badges/achievements |
| Loyalty points | ❌ Chưa có |

---

## F. EXISTING PAYMENT

| Thành phần | Hiện trạng |
|-----------|-----------|
| `PaymentService` | ✅ Invoice/payment/refund/wallet |
| `InvoiceModel` + `PaymentModel` + `RefundModel` | ✅ |
| Payment Ledger | ✅ Idempotency + row lock |
| Wallet Ledger | ✅ Topup/payment/refund/adjust |
| Bank QR | ✅ `PaymentQrConfigModel` |
| POS | ✅ Orders + payment |
| Corporate receivables | 🟡 Invoice outstanding — thiếu credit limit/billing cycle |
| Daily closing | ❌ Chưa có |
| Cash management | ❌ Chưa có |
| Tax/VAT abstraction | 🟡 Payment layer — chưa rõ ràng |

---

## G. EXISTING RATING

| Thành phần | Hiện trạng |
|-----------|-----------|
| `RatingEngine` | ✅ Internal rating provider |
| `RatingCalculator` | ✅ |
| `RatingReliabilityEngine` | ✅ Reliability score |
| `RatingEligibilityService` | ✅ Verification weight + policy |
| `RatingImportService` | ✅ Import workflow |
| `ExternalRatingProviderAdapter` | ✅ DUPR adapter |
| `RatingLedgerModel` | ✅ Immutable history |
| `RatingRebuildService` | ✅ CLI rebuild + drift report |
| `RatingGovernanceController` | ✅ Adjust/verify/flags |
| Open Play → Rating | ✅ Match records → official result flow |

---

## H. EXISTING RANKING

| Thành phần | Hiện trạng |
|-----------|-----------|
| `RankingNetworkService` | ✅ Network-level ranking |
| `RankingPointLedgerModel` | ✅ Point ledger |
| `RankingSnapshotModel` | ✅ Snapshots |
| `RankingAuthorityModel` | ✅ Authorities |
| `RankingPolicyModel` + versions | ✅ Policies |
| `RankingRebuildService` | ✅ CLI rebuild |
| Club ranking | 🟡 Có club context — Club Championship chưa có |
| Public leaderboard | ✅ `/ranking` public page |

---

## I. MISSING FEATURES

Ưu tiên theo giá trị vận hành cho chủ sân:

### P0 — Owner Command Center (phải có)
1. **Venue Owner Dashboard** (#1) — Dashboard riêng cho OWNER với real-time: Doanh thu hôm nay, booking, giờ sân đã bán, công suất, khách, khách mới/quay lại, Open Play, Tournament, No-show, Cancellation. So sánh Today/Yesterday/This Week/This Month + phân tách Revenue theo Booking/Membership/Tournament/Other.
2. **Court Occupancy Intelligence** (#2-3) — Tính Available Hours, Booked Hours, Tournament Hours, Maintenance Hours, Blocked Hours + Revenue per court (revenue, bookings, hours sold, avg booking value, occupancy).
3. **Front Desk Mode** (#23) — Một màn hình quầy cho nhân viên: search customer, create booking, check-in, payment, register tournament, register open play.
4. **No-show Engine** (#27) — Policy configurable (3 no-show → deposit required), theo dõi no-show count, cancellation count, late cancellation.
5. **Membership Renewal Dashboard** (#10) — Expiring Today/7 Days/30 Days + action Renew.

### P1 — Vận hành kinh doanh
6. **Smart Price Rule** (#6) — Occupancy > 80% → suggest increase; < 30% → suggest promotion. Không auto-change nếu chưa bật Auto Pricing.
7. **Multiple Pricing Plans** (#7) — Standard/Member/VIP/Coach/Tournament/Corporate.
8. **Corporate Booking** (#30-31) — Corporate account + package (50 hours/month) + credit limit + billing cycle.
9. **CRM Campaign** (#50-51) — Campaign (Inactive Players, Birthday, Membership Expiring, Tournament Promotion) + dynamic segments (VIP, Frequent, Inactive, New, Tournament Player, High Value).
10. **Automated Reminder** (#52) — Booking 24h/2h, tournament registration closing, membership 7 days.
11. **Staff Operations** (#54-58) — Shifts, attendance, tasks, shift handover.
12. **Daily Closing** (#64-65) — Cash/card/online reconciliation + cash management.
13. **Low Stock Alert** (#21) — Quantity < Minimum → LOW STOCK.

### P2 — Tăng trưởng & cộng đồng
14. **Loyalty Points** (#46) — Booking/Tournament/Open Play/Referral → Voucher/Free Court/Merchandise.
15. **Smart Business Insights** (#82-83) — Data-driven insights + demand forecast.
16. **Alert/Task Engine** (#88-89) — Alerts (court conflict, payment overdue, membership expiring, low inventory, maintenance, tournament issue, low occupancy, high cancellation) → task generation.
17. **Setup Wizard + Onboarding** (#94-96) — 0% → 100% progress.
18. **Import Assistant** (#97) — Excel import với mapping/preview/deduplicate.

### P3 — Công khai & đa chi nhánh
19. **Public Venue Page** (#70-73) — /venues/{slug} SEO-ready + amenities + announcement + reviews moderation.
20. **Multi-Venue Dashboard** (#80-81) — Revenue per venue, occupancy, players, bookings, tournament + venue comparison.
21. **Coach Commission** (#37) — Percentage/fixed/monthly + Coach Revenue vs Venue Revenue.
22. **Incident Management** (#59-61) — Court issue, customer complaint, equipment damage, injury report + service recovery.
23. **Owner AI Assistant** (#85) — Q&A từ dữ liệu tenant + AI actions (non-destructive).

---

## J. DUPLICATE FEATURES

| Module | Trùng lặp nguy cơ |
|--------|------------------|
| CRM Customer | `customers` mới migration `2026-08-10-100000` — KHÔNG trùng với `players`. Đã tách đúng: customer = khách hàng của venue, player = hệ thống giải đấu quốc gia. Cần giữ nguyên. |
| Ownership | `courts.court_type_id` vs `courts.club_id` vs `facility_club_assignments` — đã rõ ràng |
| Occupancy | `FacilityApi::utilization` + `OperationsDashboardService::get()` + `FacilityController::report` — cần gộp về 1 `CourtOccupancyService` duy nhất |
| Revenue by Court | `FacilityApi::revenueByCourt` — chưa có UI admin; dashboard + report nên dùng chung service |
| Peak Hours | `FacilityApi::peakHours` — cần gộp vào `CourtOccupancyService` |
| No-show | `customers.no_show_count` + `booking_logs` — cần `NoShowEngine` đọc từ booking state machine |
| Staff tasks | `jobs` (queue) vs `staff_tasks` (mới) — không trùng, queue là hệ thống xử lý nền, staff_tasks là việc vận hành |

---

## K. ARCHITECTURE RISKS

| Rủi ro | Mức độ | Giải pháp |
|--------|--------|-----------|
| Dashboard hiện tại gộp lẫn owner/staff | Cao | Tách `VenueOwnerDashboardController` + `OwnerDashboardService` cho owner; giữ `OperationsDashboardService` cho staff |
| Occupancy logic nằm rải rác API/controller | Cao | Tạo `CourtOccupancyService` duy nhất |
| No-show policy chưa có | Trung bình | Tạo `NoShowPolicyModel` + config qua `SettingService` |
| Loyalty/Campaign chưa có | Trung bình | Migration mới cho `loyalty_points`, `loyalty_ledger`, `crm_campaigns`, `crm_segments` |
| Front Desk cần AJAX nhiều | Trung bình | Xây trên nền `OpsAjaxController` hiện có |
| Pricing plans thiếu VIP/Corporate | Trung bình | Mở rộng `PricingRuleConditionModel` thêm `plan_type` |
| Corporate package chưa có | Trung bình | Migration mới `corporate_accounts`, `corporate_packages`, `corporate_bookings` |
| Import Excel cần thư viện | Thấp | Dùng PhpSpreadsheet (composer) |
| Owner AI Assistant phức tạp | Thấp | Phase cuối — chỉ trả lời từ dữ liệu tenant, không destructive actions |

---

## L. DATABASE CHANGES

### Migration mới cần tạo (theo thứ tự):

```sql
-- 1. Block courts (mở rộng từ maintenance)
court_blocks {
    id, tenant_id, branch_id, court_id,
    block_type, -- maintenance, private_event, tournament, staff, other
    reason, start_at, end_at,
    status, created_by, created_at, updated_at, deleted_at
}

-- 2. Equipment
equipment_items {
    id, tenant_id, branch_id,
    name, category, -- net, lights, scoreboard, ball_machine, racket, benches
    quantity, status, notes, created_at, updated_at, deleted_at
}

-- 3. Smart pricing
pricing_plan_types {
    id, tenant_id, code, name, -- standard, member, vip, coach, tournament, corporate
    is_active, created_at
}

smart_price_rules {
    id, tenant_id, branch_id, court_id, plan_type_id,
    threshold_high, threshold_low, -- occupancy 80%, 30%
    action_high, -- suggest_increase
    action_low, -- suggest_promotion
    auto_apply, is_active, created_at
}

-- 4. Membership entitlement mở rộng
membership_packages_entitlements {
    id, membership_package_id,
    entitlement_type, -- booking_quota, discount_percent, free_open_play, locker, etc.
    value, unit, created_at
}

-- 5. Corporate
corporate_accounts {
    id, tenant_id, branch_id, company_name, contact_name, contact_phone, contact_email,
    contract_no, credit_limit, billing_cycle, -- monthly, quarterly
    status, notes, created_at, updated_at
}

corporate_packages {
    id, tenant_id, corporate_account_id,
    package_name, total_hours, used_hours, start_date, end_date,
    status, created_at, updated_at
}

-- 6. No-show policy
no_show_policies {
    id, tenant_id,
    no_show_threshold, -- 3
    late_cancellation_minutes, -- e.g. 120
    action_type, -- deposit_required, warning_only
    is_active, created_at
}

-- 7. Loyalty
loyalty_points {
    id, tenant_id, customer_id,
    points_balance, points_expiring, updated_at
}

loyalty_ledger {
    id, tenant_id, customer_id,
    event_type, -- booking, tournament, open_play, referral, redeem
    source_type, source_id, points, balance_after,
    metadata, created_at
}

loyalty_rewards {
    id, tenant_id, name, points_cost, reward_type, -- voucher, free_court, merchandise
    value, is_active, created_at
}

-- 8. CRM Campaign
crm_campaigns {
    id, tenant_id, name, audience_segment, -- inactive_players, birthday, membership_expiring, tournament_promo
    channel, -- in_app, email, sms, push, zalo
    status, scheduled_at, sent_at, created_at, updated_at
}

crm_segments {
    id, tenant_id, code, name, criteria, -- JSON dynamic
    is_active, created_at
}

-- 9. Staff operations
staff_shifts {
    id, tenant_id, branch_id, user_id,
    shift_date, start_time, end_time, status, created_at, updated_at
}

staff_attendance {
    id, tenant_id, user_id, shift_id,
    check_in_at, check_out_at, status, -- present, absent, late
    created_at
}

staff_tasks {
    id, tenant_id, branch_id, assigned_to, created_by,
    title, description, priority, due_at,
    status, -- pending, in_progress, done, cancelled
    source_type, source_id, created_at, updated_at
}

shift_handovers {
    id, tenant_id, branch_id, from_user_id, to_user_id, shift_id,
    revenue_summary, outstanding_summary, incidents, notes,
    handed_over_at, created_at, updated_at
}

-- 10. Daily closing
daily_closings {
    id, tenant_id, branch_id, closing_date, closed_by,
    opening_cash, cash_sales, card_sales, online_sales,
    refunds, closing_cash, expected_cash, variance,
    outstanding_invoices, notes, status, created_at
}

-- 11. Incident + Service recovery
incidents {
    id, tenant_id, branch_id,
    incident_type, -- court_issue, customer_complaint, equipment_damage, injury
    severity, -- low, medium, high, critical
    reporter_id, description, status, -- open, investigating, resolved, closed
    resolution, resolved_by, resolved_at,
    created_at, updated_at
}

service_recoveries {
    id, tenant_id, incident_id, customer_id,
    action_type, -- refund, voucher, follow_up
    amount, status, created_by, created_at, updated_at
}

-- 12. Venue profile
venue_profiles {
    id, tenant_id, branch_id, slug, -- /venues/{slug}
    name, description, logo, banner, photos, -- JSON
    address, amenities, -- JSON (parking, locker, shower, cafe, equipment_rental, coach)
    opening_hours_text, booking_link, is_published, created_at, updated_at
}

venue_announcements {
    id, tenant_id, branch_id,
    title, content, type, -- holiday_hours, tournament, maintenance, promotion
    published_at, expires_at, is_published, created_at
}

-- 13. Owner insights
venue_insights {
    id, tenant_id, branch_id,
    insight_type, -- occupancy_peak, low_occupancy, court_underperform, inactive_players
    message_vi, message_en, data, -- JSON
    status, -- new, read, dismissed
    created_at
}

-- 14. Front desk
front_desk_actions {
    id, tenant_id, user_id, action_type,
    -- search_customer, create_booking, checkin, payment, register_tournament, register_open_play
    source_type, source_id,
    payload, created_at
}
```

**Tổng cộng:** ~20 migration mới.

---

## M. IMPLEMENTATION PLAN

### Phase 1 — Owner Command Center (P0)
Focus: Giúp chủ sân trả lời "Hôm nay sân thế nào?"

1. `CourtOccupancyService` (Available/Booked/Tournament/Maintenance/Blocked hours) + Revenue per court
2. `VenueOwnerDashboardController` + `OwnerDashboardService` — real-time KPI + revenue comparison (Today/Yesterday/Week/Month)
3. `FrontDeskController` + view — một màn hình quầy (search customer, create booking, check-in, payment, register tournament/open play)
4. `NoShowService` + `NoShowPolicyModel` — policy configurable (3 no-show → deposit required)
5. `MembershipRenewal` — dashboard expiring today/7/30 days + action renew
6. `SmartPriceRuleService` — occupancy > 80% suggest increase, < 30% suggest promotion (không auto-apply)
7. Menu `OWNER COMMAND CENTER` trong sidebar

**Migration:** court_blocks, no_show_policies, smart_price_rules

### Phase 2 — Corporate + Membership + Revenue (P1)
Focus: Bán gói, bán cho doanh nghiệp, thu tiền đúng.

8. `CorporateService` + models — corporate accounts/packages/credit limit/billing cycle
9. `MembershipEntitlementService` — booking quota, discount, free open play, auto-check
10. `MultiplePricingPlansService` — Standard/Member/VIP/Coach/Tournament/Corporate pricing plans
11. `DynamicPricingConfig` — Auto Pricing toggle (owner bật mới tự thay đổi giá)
12. `LoyaltyService` + models — points from booking/tournament/open play/referral, redeem voucher/free court/merchandise
13. `DailyClosingService` — cash/card/online reconciliation
14. `LowStockAlert` — quantity < minimum → LOW STOCK

**Migration:** corporate_accounts, corporate_packages, pricing_plan_types, membership_packages_entitlements, loyalty_points, loyalty_ledger, loyalty_rewards, daily_closings

### Phase 3 — Staff Ops + CRM (P1)
Focus: Vận hành nhân sự + giữ chân khách.

15. `StaffOperationService` — shifts/attendance/tasks/handover
16. `CrmCampaignService` — campaigns (inactive players, birthday, membership expiring, tournament promotion) + dynamic segments
17. `AutomatedReminder` — booking 24h/2h, tournament closing, membership 7 days (dùng job queue hiện có)
18. `IncidentService` + `ServiceRecoveryService` — incident + refund/voucher/follow-up + audit
19. `VenueAnnouncementService` — holiday hours/tournament/maintenance/promotion

**Migration:** staff_shifts, staff_attendance, staff_tasks, shift_handovers, crm_campaigns, crm_segments, incidents, service_recoveries, venue_announcements

### Phase 4 — Public Venue + Multi-branch (P2)
Focus: Chủ sân có mặt công khai + quản lý nhiều cơ sở.

20. `VenueProfileService` — /venues/{slug} SEO + amenities + media + reviews moderation
21. `MultiVenueDashboard` — revenue per venue, occupancy, players, bookings, tournament + venue comparison
22. `VenueInsightService` — data-driven insights (dựa trên dữ liệu thực, không bịa)
23. `CoachCommissionService` — percentage/fixed/monthly + coach revenue vs venue revenue
24. `AlertEngine` + `TaskEngine` — alerts → tasks (court conflict, payment overdue, membership expiring, low inventory, maintenance, tournament issue, low occupancy, high cancellation)

**Migration:** venue_profiles, venue_insights, alert_events, task_events, coach_commissions

### Phase 5 — Onboarding + Import + AI (P2/P3)
Focus: Chủ sân mới vào nhanh.

25. `OwnerOnboardingService` — wizard 0% → 100% (create org → venue → courts → pricing → staff → import customers → publish)
26. `ImportAssistantService` — Excel import, column mapping, preview, duplicate detection, confirm
27. `OwnerAiAssistantService` — Q&A từ dữ liệu tenant (doanh thu tuần này, sân yếu, khung giờ trống, khách VIP sắp hết membership, giải tháng trước) — mỗi câu trả lời link tới source data
28. `AiActionService` — đề xuất non-destructive actions (create promotion, contact inactive, add open play)

**Migration:** onboarding_progress, import_jobs, ai_assistant_queries

### Phase 6 — Hardening + Tests
29. Feature tests cho từng phase: multi-tenant isolation, occupancy, no-show policy, corporate package, loyalty redemption, staff shift, daily closing, public venue
30. Full regression test (171 tests hiện tại + mới)
31. Cập nhật docs ROADMAP/CURRENT_SYSTEM_AUDIT/GLOSSARY

---

## KẾT LUẬN AUDIT

Hệ thống hiện tại đã có **nền tảng rất mạnh** cho Venue OS:
- Booking Core, Payment, Membership, Open Play, Tournament, Rating, Ranking, Governance — đều đã hoàn chỉnh và hardened
- Nhiều feature trong 105 yêu cầu đã có sẵn (booking, waitlist, recurring, walk-in, open play, coaching, competition, promotion, notification, SaaS plan, governance)

**Khoảng trống chính** tập trung ở tầng **OWNER/CLUB OPERATIONS**:
1. Owner Command Center (dashboard, occupancy intelligence, front desk)
2. Corporate & Membership Entitlement nâng cao
3. Staff Operations (shifts, tasks, handover)
4. CRM Campaign & Loyalty
5. Public Venue Page
6. Smart Insights & Onboarding

**Kiến trúc đề xuất:** Thêm vào hệ thống hiện có, không tạo hệ thống độc lập. Dùng chung Customer/Player/Booking/Court/Match/Tournament/Payment/Rating/Ranking.
