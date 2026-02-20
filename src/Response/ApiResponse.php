<?php

class ApiResponse
{
    private static function base(): array
    {
        return [
            'meta' => [
                'api' => [
                    'version'       => '1.0',
                    'documentation' => 'https://github.com/OpenParliamentTV/OpenParliamentTV-Additional-Data-Service',
                    'license'       => [
                        'label' => 'ODC Open Database License (ODbL) v1.0',
                        'link'  => 'https://opendatacommons.org/licenses/odbl/1-0/'
                    ]
                ],
                'requestStatus' => 'error'
            ]
        ];
    }

    public static function success(array $data): array
    {
        $response = self::base();
        $response['meta']['requestStatus'] = 'success';
        $response['data'] = $data;
        return $response;
    }

    public static function error(string $info, string $field): array
    {
        $response = self::base();
        $response['errors'][] = ['info' => $info, 'field' => $field];
        return $response;
    }
}
