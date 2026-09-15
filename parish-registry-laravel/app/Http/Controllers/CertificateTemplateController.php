<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CertificateTemplateController extends Controller
{
    private const SACRAMENTS = ['Baptism', 'Communion', 'Confirmation', 'Marriage', 'Death'];

    public function update(Request $request, string $sacrament)
    {
        if (!in_array($sacrament, self::SACRAMENTS, true)) abort(404);

        $values = $request->validate(['title_text' => ['required', 'string', 'max:150'], 'body_template' => ['required', 'string'], 'footer_note' => ['required', 'string', 'max:255']]);
        $template = SettingsController::template($sacrament);
        $template->update(['title_text' => trim(strip_tags($values['title_text'])), 'body_template' => $values['body_template'], 'footer_note' => trim(strip_tags($values['footer_note']))]);

        return response()->json(['data' => $template->fresh()]);
    }
}
