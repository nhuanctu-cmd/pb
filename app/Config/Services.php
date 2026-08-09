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
}
