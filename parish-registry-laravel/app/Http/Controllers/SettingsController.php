<?php

namespace App\Http\Controllers;

use App\Models\{CertificateTemplate, ParishSetting};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private const TEMPLATES = [
        'Baptism' => ['Certificate of Baptism', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Baptism in this Parish on {eventDate}, according to the rites of the Roman Catholic Church.'],
        'Communion' => ['Certificate of First Holy Communion', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received First Holy Communion in this Parish on {eventDate}.'],
        'Confirmation' => ['Certificate of Confirmation', 'This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Confirmation in this Parish on {eventDate}.'],
        'Marriage' => ['Certificate of Marriage', 'This is to certify that {name} and {spouse} were joined in Holy Matrimony in this Parish on {eventDate}, according to the rites of the Roman Catholic Church.'],
        'Death' => ['Certificate of Death', 'This is to certify that {name}, born on {dob}, departed this life and was given ecclesiastical rites in this Parish on {eventDate}.'],
    ];

    public static function parish(): ParishSetting
    {
        return ParishSetting::firstOrCreate(['id' => 1], ['parish_name' => 'Parish of Our Lady of the Assumption', 'diocese_name' => 'Diocese of San Ildefonso', 'address' => '', 'default_priest_name' => '']);
    }

    public static function template(string $sacrament): CertificateTemplate
    {
        [$title, $body] = self::TEMPLATES[$sacrament] ?? self::TEMPLATES['Baptism'];
        return CertificateTemplate::firstOrCreate(['sacrament_type' => $sacrament], ['title_text' => $title, 'body_template' => $body, 'footer_note' => 'Not valid without the parish dry seal.']);
    }

    private function data(): array
    {
        foreach (array_keys(self::TEMPLATES) as $sacrament) self::template($sacrament);
        return ['parish' => self::parish()->fresh(), 'templates' => CertificateTemplate::whereIn('sacrament_type', array_keys(self::TEMPLATES))->get()->keyBy('sacrament_type')];
    }

    public function show() { return response()->json(['data' => $this->data()]); }
    public function preview() { return response()->json(['data' => $this->data()]); }

    public function update(Request $request)
    {
        $values = $request->validate(['parish_name' => ['required', 'string', 'max:200'], 'diocese_name' => ['required', 'string', 'max:200'], 'address' => ['nullable', 'string', 'max:255'], 'default_priest_name' => ['nullable', 'string', 'max:200']]);
        $values['address'] = $values['address'] ?? '';
        $values['default_priest_name'] = $values['default_priest_name'] ?? '';
        self::parish()->update($values);
        return response()->json(['data' => $this->data()]);
    }

    private function upload(Request $request, string $field, string $column)
    {
        $request->validate([$field => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
        self::parish()->update([$column => $request->file($field)->store('uploads', 'public')]);
        return response()->json(['data' => $this->data()]);
    }

    public function uploadSeal(Request $request) { return $this->upload($request, 'seal', 'seal_image_path'); }
    public function uploadSignature(Request $request) { return $this->upload($request, 'signature', 'priest_signature_path'); }

    public function image(Request $request, string $type)
    {
        if (!in_array($type, ['seal', 'signature'], true)) abort(404);
        $column = $type === 'seal' ? 'seal_image_path' : 'priest_signature_path';
        $path = self::parish()->{$column};
        if (!$path || !Storage::disk('public')->exists($path)) return response()->json(['error' => 'Image not found'], 404);
        return response(Storage::disk('public')->get($path), 200, ['Content-Type' => Storage::disk('public')->mimeType($path)]);
    }
}
