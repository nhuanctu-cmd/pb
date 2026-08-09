<?php

return [
    'courts'                 => 'Courts',
    'court'                  => 'Court',
    'create'                 => 'Add Court',
    'edit'                   => 'Edit Court',
    'delete'                 => 'Delete Court',
    'gridView'               => 'Grid View',
    'calendar'               => 'Calendar',
    'scheduleMaintenance'    => 'Schedule Maintenance',
    'addMaintenance'         => 'Add Maintenance',

    // Fields
    'code'                   => 'Court Code',
    'codeHint'               => 'e.g. S01, S02, A01',
    'name_vi'                => 'Name (Vietnamese)',
    'name_en'                => 'Name (English)',
    'courtType'              => 'Court Type',
    'floor'                  => 'Floor',
    'area'                   => 'Area',
    'sortOrder'              => 'Sort Order',
    'status'                 => 'Status',
    'features'               => 'Features',
    'isIndoor'               => 'Indoor',
    'hasLight'               => 'Lighting',
    'hasFan'                 => 'Fan',
    'hasCamera'              => 'Camera',
    'images'                 => 'Images',
    'uploadImage'            => 'Upload Image',
    'noImages'               => 'No images uploaded',

    // Status
    'statusAvailable'        => 'Available',
    'statusOccupied'         => 'Occupied',
    'statusMaintenance'      => 'Maintenance',
    'statusInactive'         => 'Inactive',
    'changeStatus'           => 'Change Status',
    'newStatus'              => 'New Status',

    // Maintenance
    'startTime'              => 'Start Time',
    'endTime'                => 'End Time',
    'endTimeHint'            => 'Leave empty if unknown',
    'reason'                 => 'Reason',
    'maintenanceHistory'     => 'Maintenance History',
    'noMaintenance'          => 'No maintenance records',
    'maintenanceConflict'    => 'Maintenance schedule conflicts with existing one',
    'maintenanceCreated'     => 'Maintenance scheduled successfully',
    'startDoing'             => 'Start Doing',
    'complete'               => 'Complete',
    'cancelMaintenance'      => 'Cancel',

    // Calendar
    'date'                   => 'Date',
    'time'                   => 'Time',
    'book'                   => 'Book',
    'occupied'               => 'Occupied',
    'maintenance'            => 'Maintenance',
    'selectBranchViewCalendar' => 'Select a branch to view calendar',

    // Messages
    'createdSuccess'         => 'Court created successfully',
    'updatedSuccess'         => 'Court updated successfully',
    'deletedSuccess'         => 'Court deleted successfully',
    'codeExists'             => 'Court code already exists in this branch',
    'hasBookings'            => 'Cannot delete court that has active bookings',
    'noCourts'               => 'No courts in this branch',
    'selectBranch'           => 'Please select a branch to view courts',
    'maintenanceCompleted'   => 'Maintenance completed',
    'maintenanceCancelled'   => 'Maintenance cancelled',
];
