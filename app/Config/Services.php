<?php

namespace Config;

use CodeIgniter\Config\Services as CoreServices;
use CodeIgniter\Config\Factories;

class Services extends CoreServices
{
    public static function apiResponseService(bool $getShared = true): \App\Services\ApiResponseService
    {
        if ($getShared) {
            return static::getSharedInstance('apiResponseService');
        }
        return new \App\Services\ApiResponseService();
    }

    public static function tenantService(bool $getShared = true): \App\Services\TenantService
    {
        if ($getShared) {
            return static::getSharedInstance('tenantService');
        }
        return new \App\Services\TenantService();
    }

    public static function settingService(bool $getShared = true): \App\Services\SettingService
    {
        if ($getShared) {
            return static::getSharedInstance('settingService');
        }
        return new \App\Services\SettingService();
    }

    public static function auditLogService(bool $getShared = true): \App\Services\AuditLogService
    {
        if ($getShared) {
            return static::getSharedInstance('auditLogService');
        }
        return new \App\Services\AuditLogService();
    }

    public static function permissionService(bool $getShared = true): \App\Services\PermissionService
    {
        if ($getShared) {
            return static::getSharedInstance('permissionService');
        }
        return new \App\Services\PermissionService();
    }

    public static function uploadService(bool $getShared = true): \App\Services\UploadService
    {
        if ($getShared) {
            return static::getSharedInstance('uploadService');
        }
        return new \App\Services\UploadService();
    }

    public static function courtService(bool $getShared = true): \App\Services\CourtService
    {
        if ($getShared) {
            return static::getSharedInstance('courtService');
        }
        return new \App\Services\CourtService();
    }

    public static function bookingService(bool $getShared = true): \App\Services\BookingService
    {
        if ($getShared) {
            return static::getSharedInstance('bookingService');
        }
        return new \App\Services\BookingService();
    }

    public static function availabilityService(bool $getShared = true): \App\Services\AvailabilityService
    {
        if ($getShared) return static::getSharedInstance('availabilityService');
        return new \App\Services\AvailabilityService();
    }

    public static function dataQualityService(bool $getShared = true): \App\Services\DataQualityService
    {
        if ($getShared) return static::getSharedInstance('dataQualityService');
        return new \App\Services\DataQualityService();
    }

    public static function queueMonitorService(bool $getShared = true): \App\Services\QueueMonitorService
    {
        if ($getShared) return static::getSharedInstance('queueMonitorService');
        return new \App\Services\QueueMonitorService();
    }

    public static function recurringBookingService(bool $getShared = true): \App\Services\RecurringBookingService
    {
        if ($getShared) {
            return static::getSharedInstance('recurringBookingService');
        }
        return new \App\Services\RecurringBookingService();
    }

    public static function bookingWaitlistService(bool $getShared = true): \App\Services\BookingWaitlistService
    {
        if ($getShared) {
            return static::getSharedInstance('bookingWaitlistService');
        }
        return new \App\Services\BookingWaitlistService();
    }

    public static function walkInService(bool $getShared = true): \App\Services\WalkInService
    {
        if ($getShared) {
            return static::getSharedInstance('walkInService');
        }
        return new \App\Services\WalkInService();
    }

    public static function operationsDashboardService(bool $getShared = true): \App\Services\OperationsDashboardService
    {
        if ($getShared) {
            return static::getSharedInstance('operationsDashboardService');
        }
        return new \App\Services\OperationsDashboardService();
    }

    public static function operationsReportService(bool $getShared = true): \App\Services\OperationsReportService
    {
        if ($getShared) {
            return static::getSharedInstance('operationsReportService');
        }
        return new \App\Services\OperationsReportService();
    }

    public static function openPlayService(bool $getShared = true): \App\Services\OpenPlayService
    {
        if ($getShared) {
            return static::getSharedInstance('openPlayService');
        }
        return new \App\Services\OpenPlayService();
    }

    public static function openPlayRotationService(bool $getShared = true): \App\Services\OpenPlayRotationService
    {
        if ($getShared) {
            return static::getSharedInstance('openPlayRotationService');
        }
        return new \App\Services\OpenPlayRotationService();
    }

    public static function socialGraphService(bool $getShared = true): \App\Services\SocialGraphService
    {
        if ($getShared) {
            return static::getSharedInstance('socialGraphService');
        }
        return new \App\Services\SocialGraphService();
    }

    public static function coachingService(bool $getShared = true): \App\Services\CoachingService
    {
        if ($getShared) {
            return static::getSharedInstance('coachingService');
        }
        return new \App\Services\CoachingService();
    }

    public static function competitionService(bool $getShared = true): \App\Services\CompetitionService
    {
        if ($getShared) {
            return static::getSharedInstance('competitionService');
        }
        return new \App\Services\CompetitionService();
    }

    public static function communityService(bool $getShared = true): \App\Services\CommunityService
    {
        if ($getShared) return static::getSharedInstance('communityService');
        return new \App\Services\CommunityService();
    }

    public static function aiSchedulingService(bool $getShared = true): \App\Services\AiSchedulingService
    {
        if ($getShared) return static::getSharedInstance('aiSchedulingService');
        return new \App\Services\AiSchedulingService();
    }

    public static function livestreamService(bool $getShared = true): \App\Services\LivestreamService
    {
        if ($getShared) return static::getSharedInstance('livestreamService');
        return new \App\Services\LivestreamService();
    }

    public static function webhookService(bool $getShared = true): \App\Services\WebhookService
    {
        if ($getShared) return static::getSharedInstance('webhookService');
        return new \App\Services\WebhookService();
    }

    public static function partnerApiService(bool $getShared = true): \App\Services\PartnerApiService
    {
        if ($getShared) return static::getSharedInstance('partnerApiService');
        return new \App\Services\PartnerApiService();
    }

    public static function playerPassportService(bool $getShared = true): \App\Services\PlayerPassportService
    {
        if ($getShared) return static::getSharedInstance('playerPassportService');
        return new \App\Services\PlayerPassportService();
    }

    public static function internationalFoundationService(bool $getShared = true): \App\Services\InternationalFoundationService
    {
        if ($getShared) return static::getSharedInstance('internationalFoundationService');
        return new \App\Services\InternationalFoundationService();
    }

    public static function tenantDataPolicy(bool $getShared = true): \App\Services\TenantDataPolicy
    {
        if ($getShared) return static::getSharedInstance('tenantDataPolicy');
        return new \App\Services\TenantDataPolicy();
    }

    public static function ratingNetworkService(bool $getShared = true): \App\Services\RatingNetworkService
    {
        if ($getShared) return static::getSharedInstance('ratingNetworkService');
        return new \App\Services\RatingNetworkService();
    }

    public static function rankingNetworkService(bool $getShared = true): \App\Services\RankingNetworkService
    {
        if ($getShared) return static::getSharedInstance('rankingNetworkService');
        return new \App\Services\RankingNetworkService();
    }

    public static function tournamentMatchNetworkAdapter(bool $getShared = true): \App\Services\TournamentMatchNetworkAdapter
    {
        if ($getShared) return static::getSharedInstance('tournamentMatchNetworkAdapter');
        return new \App\Services\TournamentMatchNetworkAdapter();
    }

    public static function platformClubService(bool $getShared = true): \App\Services\PlatformClubService
    {
        if ($getShared) return static::getSharedInstance('platformClubService');
        return new \App\Services\PlatformClubService();
    }

    public static function matchGovernanceService(bool $getShared = true): \App\Services\MatchGovernanceService
    {
        if ($getShared) return static::getSharedInstance('matchGovernanceService');
        return new \App\Services\MatchGovernanceService();
    }

    public static function growthService(bool $getShared = true): \App\Services\GrowthService
    {
        if ($getShared) {
            return static::getSharedInstance('growthService');
        }
        return new \App\Services\GrowthService();
    }

    public static function teamService(bool $getShared = true): \App\Services\TeamService
    {
        if ($getShared) {
            return static::getSharedInstance('teamService');
        }
        return new \App\Services\TeamService();
    }

    public static function matchingService(bool $getShared = true): \App\Services\MatchingService
    {
        if ($getShared) {
            return static::getSharedInstance('matchingService');
        }
        return new \App\Services\MatchingService();
    }

    public static function scoreService(bool $getShared = true): \App\Services\ScoreService
    {
        if ($getShared) {
            return static::getSharedInstance('scoreService');
        }
        return new \App\Services\ScoreService();
    }

    public static function liveScoreService(bool $getShared = true): \App\Services\LiveScoreService
    {
        if ($getShared) {
            return static::getSharedInstance('liveScoreService');
        }
        return new \App\Services\LiveScoreService();
    }

    public static function publicPortalService(bool $getShared = true): \App\Services\PublicPortalService
    {
        if ($getShared) return static::getSharedInstance('publicPortalService');
        return new \App\Services\PublicPortalService();
    }

    public static function ratingEngine(bool $getShared = true): \App\Services\RatingEngine
    {
        if ($getShared) return static::getSharedInstance('ratingEngine');
        return new \App\Services\RatingEngine();
    }

    public static function ratingCalculator(bool $getShared = true): \App\Services\RatingCalculator
    {
        if ($getShared) return static::getSharedInstance('ratingCalculator');
        return new \App\Services\RatingCalculator(new \App\Services\ExpectedPerformanceCalculator(), new \App\Services\MatchPerformanceService(), new \App\Services\AverageTeamRatingStrategy());
    }

    public static function ratingEligibilityService(bool $getShared = true): \App\Services\RatingEligibilityService
    {
        if ($getShared) return static::getSharedInstance('ratingEligibilityService');
        return new \App\Services\RatingEligibilityService();
    }

    public static function ratingReliabilityEngine(bool $getShared = true): \App\Services\RatingReliabilityEngine
    {
        if ($getShared) return static::getSharedInstance('ratingReliabilityEngine');
        return new \App\Services\RatingReliabilityEngine();
    }

    public static function skillBandResolver(bool $getShared = true): \App\Services\SkillBandResolver
    {
        if ($getShared) return static::getSharedInstance('skillBandResolver');
        return new \App\Services\SkillBandResolver();
    }

    public static function initialRatingService(bool $getShared = true): \App\Services\InitialRatingService
    {
        if ($getShared) return static::getSharedInstance('initialRatingService');
        return new \App\Services\InitialRatingService();
    }

    public static function skillAssessmentService(bool $getShared = true): \App\Services\SkillAssessmentService
    {
        if ($getShared) return static::getSharedInstance('skillAssessmentService');
        return new \App\Services\SkillAssessmentService();
    }

    public static function playerSkillClaimService(bool $getShared = true): \App\Services\PlayerSkillClaimService
    {
        if ($getShared) return static::getSharedInstance('playerSkillClaimService');
        return new \App\Services\PlayerSkillClaimService();
    }

    public static function tournamentEligibilityService(bool $getShared = true): \App\Services\TournamentEligibilityService
    {
        if ($getShared) return static::getSharedInstance('tournamentEligibilityService');
        return new \App\Services\TournamentEligibilityService();
    }

    public static function ratingRebuildService(bool $getShared = true): \App\Services\RatingRebuildService
    {
        if ($getShared) return static::getSharedInstance('ratingRebuildService');
        return new \App\Services\RatingRebuildService();
    }

    public static function rankingRebuildService(bool $getShared = true): \App\Services\RankingRebuildService
    {
        if ($getShared) return static::getSharedInstance('rankingRebuildService');
        return new \App\Services\RankingRebuildService();
    }

    public static function ratingIntegrityService(bool $getShared = true): \App\Services\RatingIntegrityService
    {
        if ($getShared) return static::getSharedInstance('ratingIntegrityService');
        return new \App\Services\RatingIntegrityService();
    }

    public static function ratingImportService(bool $getShared = true): \App\Services\RatingImportService
    {
        if ($getShared) return static::getSharedInstance('ratingImportService');
        return new \App\Services\RatingImportService();
    }

    public static function provenanceService(bool $getShared = true): \App\Services\ProvenanceService
    {
        if ($getShared) return static::getSharedInstance('provenanceService');
        return new \App\Services\ProvenanceService();
    }

    public static function rulesetService(bool $getShared = true): \App\Services\RulesetService
    {
        if ($getShared) return static::getSharedInstance('rulesetService');
        return new \App\Services\RulesetService();
    }

    public static function governanceService(bool $getShared = true): \App\Services\GovernanceService
    {
        if ($getShared) return static::getSharedInstance('governanceService');
        return new \App\Services\GovernanceService();
    }

    public static function providerRatingService(bool $getShared = true): \App\Services\ProviderRatingService
    {
        if ($getShared) return static::getSharedInstance('providerRatingService');
        return new \App\Services\ProviderRatingService();
    }

    public static function resultCorrectionService(bool $getShared = true): \App\Services\ResultCorrectionService
    {
        if ($getShared) return static::getSharedInstance('resultCorrectionService');
        return new \App\Services\ResultCorrectionService();
    }

    public static function externalRatingProviderAdapter(bool $getShared = true): \App\Services\ExternalRatingProviderAdapter
    {
        if ($getShared) return static::getSharedInstance('externalRatingProviderAdapter');
        return new \App\Services\ExternalRatingProviderAdapter();
    }

    public static function ratingAdjustmentService(bool $getShared = true): \App\Services\RatingAdjustmentService
    {
        if ($getShared) return static::getSharedInstance('ratingAdjustmentService');
        return new \App\Services\RatingAdjustmentService();
    }

    public static function tournamentService(bool $getShared = true): \App\Services\TournamentService
    {
        if ($getShared) {
            return static::getSharedInstance('tournamentService');
        }
        return new \App\Services\TournamentService();
    }

    public static function tournamentRegistrationService(bool $getShared = true): \App\Services\TournamentRegistrationService
    {
        if ($getShared) {
            return static::getSharedInstance('tournamentRegistrationService');
        }
        return new \App\Services\TournamentRegistrationService();
    }

    public static function venueOperationsService(bool $getShared = true): \App\Services\VenueOperationsService
    {
        if ($getShared) return static::getSharedInstance('venueOperationsService');
        return new \App\Services\VenueOperationsService();
    }
}
