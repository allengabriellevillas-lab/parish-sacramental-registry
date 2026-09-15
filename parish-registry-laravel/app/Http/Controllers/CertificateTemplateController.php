<?php
namespace App\Http\Controllers;
use App\Models\CertificateTemplate;use Illuminate\Http\Request;
class CertificateTemplateController extends Controller { public function update(Request $r,string $sacrament){if(!in_array($sacrament,['Baptism','Communion','Confirmation','Marriage','Death'],true))abort(404);$t=CertificateTemplate::findOrFail($sacrament);$t->update(['title_text'=>trim(strip_tags((string)$r->input('title_text'))),'body_template'=>(string)$r->input('body_template'),'footer_note'=>trim(strip_tags((string)$r->input('footer_note')))]);return response()->json(['data'=>$t]);} }
