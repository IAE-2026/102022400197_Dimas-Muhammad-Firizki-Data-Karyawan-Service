<?php

return [
    'service_name' => env('IAE_SERVICE_NAME', 'Data-Karyawan-Service'),
    'api_version' => env('IAE_API_VERSION', 'v1'),
    'api_key' => env('IAE_API_KEY', '102022400197'),
    'team_id' => env('IAE_TEAM_ID', 'TEAM-09'),
    'default_role' => env('IAE_DEFAULT_ROLE', 'hr_admin'),
    'sso_base_url' => env('IAE_SSO_BASE_URL', 'https://iae-sso.virtualfri.id'),
    'm2m_api_key' => env('IAE_M2M_API_KEY'),
    'soap_url' => env('IAE_SOAP_URL', 'https://iae-sso.virtualfri.id/soap/v1/audit'),
    'publisher_url' => env('IAE_PUBLISHER_URL', 'https://iae-sso.virtualfri.id/api/v1/messages/publish'),
    'http_timeout' => (int) env('IAE_HTTP_TIMEOUT', 15),
];
