<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

// Single source of truth for the applicant's Supporting Documents upload
// fields — shared by apply.php (upload form) and the admin/evaluator
// portals (Submitted Documents display), so every expected document type
// is always listed there even when the applicant left it blank.
const DOCUMENT_FIELDS = [
    'letter_of_intent' => 'Letter of Intent',
    'pds' => 'Personal Data Sheet (PDS)',
    'tor_diploma' => 'TOR / Diploma',
    'prc_license' => 'PRC License',
    'eligibility_file' => 'Eligibility Document',
    'training_certificates' => 'Training Certificates',
    'employment_records' => 'Employment Records',
    'latest_appointment' => 'Latest Appointment',
    'performance_rating' => 'Performance Rating',
    'cav' => 'CAV',
    'movs' => 'Other MOVs/Documents',
    'psst_ncois' => 'PSST NCOIs',
    'psst_cois' => 'PSST COIs',
];
