<?php

namespace App\Services;

use App\Models\ResponseTemplate;

class TemplateService
{
    public static function getTemplates($locationId = null)
    {
        $templateQuery = ResponseTemplate::where('location_id', $locationId);

        if (!$templateQuery->exists()) {
            $templateQuery = ResponseTemplate::whereNull('location_id');
        }

        $templates = $templateQuery->get();
        $temps = [];

        foreach ($templates as $template) {
            $templateData = json_decode($template->template, true);

            foreach ($templateData as $keyword => $message) {
                $temps[$keyword] = $message;
            }
        }

        return $temps;
    }
}
