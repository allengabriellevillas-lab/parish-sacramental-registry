<?php
declare(strict_types=1);
session_name('parish_registry'); session_start();
$cfg = file_exists(__DIR__.'/../config.php') ? require __DIR__.'/../config.php' : require __DIR__.'/../config.example.php';
date_default_timezone_set($cfg['timezone']);
require_once __DIR__.'/../vendor/autoload.php';
function db(): PDO { global $cfg; static $db; return $db ??= new PDO("mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",$cfg['db_user'],$cfg['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
function json(array $data,int $status=200) { http_response_code($status); header('Content-Type: application/json'); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit; }
function input(): array { $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:$_POST; }
function user(): array { if (empty($_SESSION['user'])) json(['error'=>'Authentication required'],401); return $_SESSION['user']; }
function allow(array $roles): array { $u=user(); if(!in_array($u['role'],$roles,true)) json(['error'=>'Forbidden'],403); return $u; }
function clean($v): string { return trim(strip_tags((string)$v)); }
function fields(array $r): array { $map=['sacrament'=>'sacrament_type','firstName'=>'first_name','middleName'=>'middle_name','lastName'=>'last_name','gender'=>'gender','dob'=>'date_of_birth','eventDate'=>'event_date','fatherName'=>'father_name','motherName'=>'mother_maiden_name','spouse'=>'spouse_name','book'=>'book_number','page'=>'page_number','line'=>'line_number','minister'=>'minister_name','sponsors'=>'sponsors','marginNotes'=>'margin_notes']; $out=[]; foreach($map as $a=>$b)$out[$b]=clean($r[$a]??$r[$b]??''); return $out; }
function validate(array $r, bool $database=true): array { $f=fields($r); $e=[]; $types=['Baptism','Communion','Confirmation','Marriage','Death']; if(!in_array($f['sacrament_type'],$types,true))$e[]='Missing Sacrament Type'; if(!$f['first_name']||!$f['last_name'])$e[]='First and last name required'; if(!in_array($f['gender'],['Male','Female'],true))$e[]='Gender must be Male or Female'; foreach(['event_date','book_number','page_number','line_number'] as $x)if(!$f[$x])$e[]="$x is required"; foreach(['date_of_birth','event_date'] as $x)if($f[$x]&&!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$f[$x]))$e[]='Invalid Date'; if($f['date_of_birth']&&$f['event_date']&&$f['event_date']<$f['date_of_birth'])$e[]='Invalid Date'; if(in_array($f['sacrament_type'],['Baptism','Communion','Confirmation'],true)){if(!$f['father_name'])$e[]='Missing Father Name';if(!$f['mother_maiden_name'])$e[]='Missing Mother Name';} if($database && !$e){$s=db()->prepare('SELECT id FROM sacramental_records WHERE sacrament_type=? AND book_number=? AND page_number=? AND line_number=?');$s->execute([$f['sacrament_type'],$f['book_number'],$f['page_number'],$f['line_number']]);if($s->fetch())$e[]='Duplicate Entry';} return [$f,array_values(array_unique($e))]; }
function record(int $id): array|false { $s=db()->prepare('SELECT r.id,r.sacrament_type sacrament,r.event_date eventDate,r.book_number book,r.page_number page,r.line_number line,r.minister_name minister,r.sponsors,r.margin_notes marginNotes,p.first_name firstName,p.middle_name middleName,p.last_name lastName,p.gender,p.date_of_birth dob,p.father_name fatherName,p.mother_maiden_name motherName,p.spouse_name spouse FROM sacramental_records r JOIN persons p ON p.id=r.person_id WHERE r.id=?');$s->execute([$id]);return $s->fetch(); }
function settings(): array { $s=db()->query('SELECT * FROM parish_settings WHERE id=1')->fetch(); $rows=db()->query('SELECT * FROM certificate_templates')->fetchAll(); $templates=[]; foreach($rows as $row)$templates[$row['sacrament_type']]=$row; return ['parish'=>$s,'templates'=>$templates]; }
function save_upload(string $field,string $prefix): ?string { if(empty($_FILES[$field])||$_FILES[$field]['error']!==UPLOAD_ERR_OK)return null; $f=$_FILES[$field]; if($f['size']>2*1024*1024)json(['error'=>'Image must be under 2MB'],422); $mime=mime_content_type($f['tmp_name']); $ext=match($mime){'image/png'=>'png','image/jpeg'=>'jpg',default=>null}; if(!$ext)json(['error'=>'Only PNG or JPEG images are allowed'],422); $dir=__DIR__.'/../uploads'; if(!is_dir($dir)&&!mkdir($dir,0755,true))json(['error'=>'Could not store image'],500); $filename=$prefix.'_'.time().'_'.bin2hex(random_bytes(6)).'.'.$ext; if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$filename))json(['error'=>'Could not store image'],500); return 'uploads/'.$filename; }
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $path=preg_replace('#^.*/api/v1#','',$path); $method=$_SERVER['REQUEST_METHOD'];
if($path==='/auth/login'&&$method==='POST'){ $d=input(); $key=clean($d['username']??''); $s=db()->prepare('SELECT id,full_name,username,email,avatar_path,role,password_hash FROM staff_users WHERE (username=? OR email=?) AND is_active=1 LIMIT 1');$s->execute([$key,$key]);$u=$s->fetch();if(!$u||!password_verify((string)($d['password']??''),$u['password_hash']))json(['error'=>'Invalid credentials'],401); $_SESSION['user']=['id'=>(int)$u['id'],'full_name'=>$u['full_name'],'username'=>$u['username'],'email'=>$u['email'],'role'=>$u['role'],'avatar_path'=>$u['avatar_path']??null];json(['data'=>$_SESSION['user']]); }
if($path==='/auth/logout'&&$method==='POST'){session_destroy();json(['data'=>true]);} if($path==='/auth/me'&&$method==='GET')json(['data'=>user()]);
if($path==='/me'&&$method==='PUT'){ $u=user(); $d=input(); $fullName=clean($d['full_name']??''); $email=clean($d['email']??''); if(!$fullName)json(['error'=>'Full name is required'],422); if($email&&!filter_var($email,FILTER_VALIDATE_EMAIL))json(['error'=>'Invalid email address'],422); try{db()->prepare('UPDATE staff_users SET full_name=?,email=? WHERE id=?')->execute([$fullName,$email?:null,$u['id']]);}catch(PDOException $e){json(['error'=>'Email address is already in use'],422);} $_SESSION['user']['full_name']=$fullName;$_SESSION['user']['email']=$email?:null;json(['data'=>$_SESSION['user']]); }
if($path==='/me/password'&&$method==='PUT'){ $u=user();$d=input();$current=(string)($d['current_password']??'');$new=(string)($d['new_password']??'');if(strlen($new)<8)json(['error'=>'New password must be at least 8 characters'],422);$s=db()->prepare('SELECT password_hash FROM staff_users WHERE id=?');$s->execute([$u['id']]);$row=$s->fetch();if(!$row||!password_verify($current,$row['password_hash']))json(['error'=>'Current password is incorrect'],422);db()->prepare('UPDATE staff_users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]);json(['data'=>true]); }
if($path==='/me/avatar'&&$method==='POST'){ $u=user();$p=save_upload('avatar','avatar_'.$u['id']);if(!$p)json(['error'=>'No valid image uploaded'],422);db()->prepare('UPDATE staff_users SET avatar_path=? WHERE id=?')->execute([$p,$u['id']]);$_SESSION['user']['avatar_path']=$p;json(['data'=>$_SESSION['user']]); }
if($path==='/me/avatar'&&$method==='GET'){ $u=user();$path=$u['avatar_path']??null;if(!$path||!preg_match('#^uploads/[A-Za-z0-9_.-]+$#',$path))json(['error'=>'Image not found'],404);$file=dirname(__DIR__).'/'.$path;if(!is_file($file))json(['error'=>'Image not found'],404);$mime=mime_content_type($file);if(!in_array($mime,['image/png','image/jpeg'],true))json(['error'=>'Image not found'],404);header('Content-Type: '.$mime);header('Content-Length: '.filesize($file));readfile($file);exit; }
if($path==='/settings'&&$method==='GET'){allow(['admin']);json(['data'=>settings()]);}
if(preg_match('#^/settings/image/(seal|signature)$#',$path,$m)&&$method==='GET'){
  allow(['admin']);
  $column=$m[1]==='seal'?'seal_image_path':'priest_signature_path';
  $path=settings()['parish'][$column]??null;
  if(!$path||!preg_match('#^uploads/[A-Za-z0-9_.-]+$#',$path))json(['error'=>'Image not found'],404);
  $file=dirname(__DIR__).'/'.$path;
  if(!is_file($file))json(['error'=>'Image not found'],404);
  $mime=mime_content_type($file); if(!in_array($mime,['image/png','image/jpeg'],true))json(['error'=>'Image not found'],404);
  header('Content-Type: '.$mime); header('Content-Length: '.filesize($file)); readfile($file); exit;
}
if($path==='/settings'&&$method==='PUT'){allow(['admin']);$d=input();db()->prepare('UPDATE parish_settings SET parish_name=?,diocese_name=?,address=?,default_priest_name=? WHERE id=1')->execute([clean($d['parish_name']??''),clean($d['diocese_name']??''),clean($d['address']??''),clean($d['default_priest_name']??'')]);json(['data'=>settings()]);}
if($path==='/settings/seal'&&$method==='POST'){allow(['admin']);$p=save_upload('seal','seal');if(!$p)json(['error'=>'Select a seal image'],422);db()->prepare('UPDATE parish_settings SET seal_image_path=? WHERE id=1')->execute([$p]);json(['data'=>settings()]);}
if($path==='/settings/priest-signature'&&$method==='POST'){allow(['admin']);$p=save_upload('signature','signature');if(!$p)json(['error'=>'Select a signature image'],422);db()->prepare('UPDATE parish_settings SET priest_signature_path=? WHERE id=1')->execute([$p]);json(['data'=>settings()]);}
if(preg_match('#^/certificate-templates/(Baptism|Communion|Confirmation|Marriage|Death)$#',$path,$m)&&$method==='PUT'){allow(['admin']);$d=input();db()->prepare('UPDATE certificate_templates SET title_text=?,body_template=?,footer_note=? WHERE sacrament_type=?')->execute([clean($d['title_text']??''),(string)($d['body_template']??''),clean($d['footer_note']??''),$m[1]]);json(['data'=>settings()['templates'][$m[1]]]);}
if($path==='/records'&&$method==='GET'){allow(['admin','staff','viewer']);$q='%'.clean($_GET['q']??'').'%';$where=' WHERE (p.first_name LIKE ? OR p.middle_name LIKE ? OR p.last_name LIKE ? OR p.date_of_birth LIKE ? OR r.sacrament_type LIKE ? OR r.book_number LIKE ? OR r.page_number LIKE ? OR r.line_number LIKE ?)';$args=array_fill(0,8,$q); foreach(['sacrament'=>'sacrament_type','dob'=>'date_of_birth','book'=>'book_number','page'=>'page_number','line'=>'line_number'] as $k=>$c)if(!empty($_GET[$k])){$where.=" AND ".(str_starts_with($c,'date_')?'p':'r').".$c=?";$args[]=clean($_GET[$k]);}$page=max(1,(int)($_GET['page_num']??1));$per=min(100,max(1,(int)($_GET['per_page']??25)));$count=db()->prepare('SELECT COUNT(*) FROM sacramental_records r JOIN persons p ON p.id=r.person_id'.$where);$count->execute($args);$s=db()->prepare('SELECT r.id,r.sacrament_type sacrament,r.event_date eventDate,r.book_number book,r.page_number page,r.line_number line,r.minister_name minister,r.sponsors,r.margin_notes marginNotes,p.first_name firstName,p.middle_name middleName,p.last_name lastName,p.gender,p.date_of_birth dob,p.father_name fatherName,p.mother_maiden_name motherName,p.spouse_name spouse FROM sacramental_records r JOIN persons p ON p.id=r.person_id'.$where.' ORDER BY r.event_date DESC LIMIT '.$per.' OFFSET '.(($page-1)*$per));$s->execute($args);json(['data'=>$s->fetchAll(),'meta'=>['total'=>(int)$count->fetchColumn(),'page'=>$page,'per_page'=>$per]]); }
if(preg_match('#^/records/(\\d+)$#',$path,$m)&&$method==='GET'){allow(['admin','staff','viewer']);$r=record((int)$m[1]);$r?json(['data'=>$r]):json(['error'=>'Not found'],404);}
if ($path === '/records' && $method === 'POST') {
  $u = allow(['admin', 'staff']);
  list($f, $errors) = validate(input());
  if ($errors) json(['error' => 'Validation failed', 'errors' => $errors], 422);
  $d = db(); $d->beginTransaction();
  try {
    $p = $d->prepare('SELECT id FROM persons WHERE first_name=? AND last_name=? AND date_of_birth <=> ? LIMIT 1');
    $p->execute([$f['first_name'], $f['last_name'], $f['date_of_birth'] ?: null]); $pid = $p->fetchColumn();
    if (!$pid) {
      $p = $d->prepare('INSERT INTO persons(first_name,middle_name,last_name,gender,date_of_birth,father_name,mother_maiden_name,spouse_name) VALUES(?,?,?,?,?,?,?,?)');
      $p->execute([$f['first_name'], $f['middle_name'] ?: null, $f['last_name'], $f['gender'], $f['date_of_birth'] ?: null, $f['father_name'] ?: null, $f['mother_maiden_name'] ?: null, $f['spouse_name'] ?: null]);
      $pid = $d->lastInsertId();
    }
    $s = $d->prepare('INSERT INTO sacramental_records(person_id,sacrament_type,event_date,book_number,page_number,line_number,minister_name,sponsors,margin_notes,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');
    $s->execute([$pid, $f['sacrament_type'], $f['event_date'], $f['book_number'], $f['page_number'], $f['line_number'], $f['minister_name'] ?: null, $f['sponsors'] ?: null, $f['margin_notes'] ?: null, $u['id']]);
    $id = (int)$d->lastInsertId(); $d->commit();
    json(['data' => record($id), 'meta' => ['next_book_number' => $f['book_number'], 'next_line_number' => (string)((int)$f['line_number'] + 1), 'dedupe_match' => (bool)$pid]], 201);
  } catch (Throwable $e) { $d->rollBack(); json(['error' => 'Could not save record'], 409); }
}
if(preg_match('#^/records/(\\d+)/issue$#',$path,$m)&&$method==='POST'){ $u=allow(['admin','staff']);$r=record((int)$m[1]);if(!$r)json(['error'=>'Not found'],404);$x=input();$name=clean($x['requestor_name']??'');$purpose=clean($x['purpose']??'');if(!$name||!$purpose)json(['error'=>'Requestor name and purpose are required'],422);$s=db()->prepare('INSERT INTO certificate_issuance_logs(sacramental_record_id,requestor_name,purpose,issued_by) VALUES(?,?,?,?)');$s->execute([$r['id'],$name,$purpose,$u['id']]);json(['data'=>['id'=>(int)db()->lastInsertId(),'sacramental_record_id'=>$r['id'],'requestor_name'=>$name,'purpose'=>$purpose]] ,201); }
if(preg_match('#^/records/(\d+)/certificate\.pdf$#',$path,$m)&&$method==='GET'){
  allow(['admin','staff']);
  $r=record((int)$m[1]);
  $log=(int)($_GET['log_id']??0);
  $s=db()->prepare('SELECT requestor_name, purpose FROM certificate_issuance_logs WHERE id=? AND sacramental_record_id=?');
  $s->execute([$log,$r['id']??0]);
  $issuance=$s->fetch();
  if(!$r||!$issuance)json(['error'=>'Issuance log required'],404);

  $html = certificate_html($r, $issuance['requestor_name'], $issuance['purpose']);

  $options = new \Dompdf\Options();
  $options->set('isRemoteEnabled', false);
  $dompdf = new \Dompdf\Dompdf($options);
  $dompdf->loadHtml($html);
  $dompdf->setPaper('letter','portrait');
  $dompdf->render();

  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="certificate-'.$r['id'].'.pdf"');
  echo $dompdf->output();
  exit;
}if($path==='/imports'&&$method==='POST'){ $u=allow(['admin','staff']);if(empty($_FILES['file'])||$_FILES['file']['error']!==UPLOAD_ERR_OK||$_FILES['file']['size']>5*1024*1024||!preg_match('/\.csv$/i',$_FILES['file']['name']))json(['error'=>'Upload a CSV no larger than 5MB'],422);$h=fopen($_FILES['file']['tmp_name'],'r');$headers=fgetcsv($h);$rows=[];while(($v=fgetcsv($h))!==false){$raw=array_combine($headers,array_pad($v,count($headers),''));[$f,$e]=validate($raw);$rows[]=['row'=>count($rows)+2,'data'=>$raw,'status'=>$e?'Invalid':'Valid','errors'=>$e];}fclose($h);$d=db();$s=$d->prepare('INSERT INTO import_batches(filename,uploaded_by,total_rows,valid_rows) VALUES(?,?,?,?)');$valid=count(array_filter($rows,fn($x)=>$x['status']==='Valid'));$s->execute([basename($_FILES['file']['name']),$u['id'],count($rows),$valid]);$id=(int)$d->lastInsertId();$s=$d->prepare('INSERT INTO import_staged_rows(batch_id,row_number,payload,validation_errors) VALUES(?,?,?,?)');foreach($rows as $r)$s->execute([$id,$r['row'],json_encode($r['data']),json_encode($r['errors'])]);json(['data'=>['batch_id'=>$id,'rows'=>$rows],'meta'=>['total'=>count($rows),'valid'=>$valid]],201); }
if(preg_match('#^/imports/(\\d+)/commit$#',$path,$m)&&$method==='POST'){ $u=allow(['admin','staff']);$d=db();$b=$d->prepare("SELECT * FROM import_batches WHERE id=? AND status='staged'");$b->execute([(int)$m[1]]);$batch=$b->fetch();if(!$batch)json(['error'=>'Staged batch not found'],404);$rows=$d->prepare('SELECT payload FROM import_staged_rows WHERE batch_id=?');$rows->execute([$batch['id']]);$d->beginTransaction();try{$n=0;foreach($rows as $row){[$f,$e]=validate(json_decode($row['payload'],true));if($e)continue;$p=$d->prepare('SELECT id FROM persons WHERE first_name=? AND last_name=? AND date_of_birth <=> ? LIMIT 1');$p->execute([$f['first_name'],$f['last_name'],$f['date_of_birth']?:null]);$pid=$p->fetchColumn();if(!$pid){$p=$d->prepare('INSERT INTO persons(first_name,middle_name,last_name,gender,date_of_birth,father_name,mother_maiden_name,spouse_name) VALUES(?,?,?,?,?,?,?,?)');$p->execute([$f['first_name'],$f['middle_name']?:null,$f['last_name'],$f['gender'],$f['date_of_birth']?:null,$f['father_name']?:null,$f['mother_maiden_name']?:null,$f['spouse_name']?:null]);$pid=$d->lastInsertId();}$s=$d->prepare("INSERT INTO sacramental_records(person_id,sacrament_type,event_date,book_number,page_number,line_number,minister_name,sponsors,margin_notes,source,created_by) VALUES(?,?,?,?,?,?,?,?,?,'bulk_import',?)");$s->execute([$pid,$f['sacrament_type'],$f['event_date'],$f['book_number'],$f['page_number'],$f['line_number'],$f['minister_name']?:null,$f['sponsors']?:null,$f['margin_notes']?:null,$u['id']]);$n++;}$d->prepare("UPDATE import_batches SET committed_rows=?,status='committed' WHERE id=?")->execute([$n,$batch['id']]);$d->commit();json(['data'=>['committed_rows'=>$n]]);}catch(Throwable $e){$d->rollBack();json(['error'=>'Import commit failed'],409);}}
if(($path==='/imports/template' || preg_match('#^/imports/(\\d+)/template$#',$path))&&$method==='GET'){allow(['admin','staff']);header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="parish_bulk_import_template.csv"');readfile(__DIR__.'/../parish_bulk_import_template.csv');exit;}
if(preg_match('#^/imports/(\\d+)$#',$path,$m)&&$method==='DELETE'){allow(['admin','staff']);db()->prepare("UPDATE import_batches SET status='discarded' WHERE id=? AND status='staged'")->execute([(int)$m[1]]);json(['data'=>true]);}
if($path==='/issuance-logs'&&$method==='GET'){allow(['admin','staff','viewer']);$page=max(1,(int)($_GET['page']??1));$per=min(100,max(1,(int)($_GET['per_page']??25)));$where='';$args=[];if(!empty($_GET['sacrament'])){$where=' WHERE r.sacrament_type=?';$args[]=clean($_GET['sacrament']);}$count=db()->prepare('SELECT COUNT(*) FROM certificate_issuance_logs l JOIN sacramental_records r ON r.id=l.sacramental_record_id'.$where);$count->execute($args);$s=db()->prepare('SELECT l.id,l.requestor_name issuedTo,l.purpose,l.issued_at timestamp,u.full_name issuedBy,r.sacrament_type sacrament,CONCAT_WS(\' \',p.first_name,p.middle_name,p.last_name) personName FROM certificate_issuance_logs l JOIN sacramental_records r ON r.id=l.sacramental_record_id JOIN persons p ON p.id=r.person_id JOIN staff_users u ON u.id=l.issued_by'.$where.' ORDER BY l.issued_at DESC LIMIT '.$per.' OFFSET '.(($page-1)*$per));$s->execute($args);json(['data'=>$s->fetchAll(),'meta'=>['total'=>(int)$count->fetchColumn(),'page'=>$page,'per_page'=>$per]]);}
if(!str_starts_with($_SERVER['REQUEST_URI'],'/api/v1')){readfile(__DIR__.'/parish-registry.html');exit;} json(['error'=>'Not found'],404);
function certificate_html(array $r, string $requestor, string $purpose): string {
    $esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
    $date = fn($v) => $v ? date('F j, Y', strtotime((string)$v)) : '—';
    $name = trim($r['firstName'].' '.($r['middleName']??'').' '.$r['lastName']);
    $settings = settings();
    $parish = $settings['parish'] ?? [];
    $template = $settings['templates'][$r['sacrament']] ?? [];
    $title = $r['sacrament'] === 'Communion' ? 'Certificate of First Holy Communion' : 'Certificate of '.$r['sacrament'];
    $today = date('F j, Y');
    $issuedTo = $requestor === 'Not specified' ? '____________________' : $esc($requestor);
    $tokens = ['{name}'=>$name, '{dob}'=>$date($r['dob']), '{fatherName}'=>$r['fatherName'], '{motherName}'=>$r['motherName'], '{eventDate}'=>$date($r['eventDate']), '{spouse}'=>$r['spouse'], '{sacrament}'=>$r['sacrament'], '{book}'=>$r['book'], '{page}'=>$r['page'], '{line}'=>$r['line']];
    $priest = trim((string)($r['minister'] ?? '')) ?: (string)($parish['default_priest_name'] ?? '');
    $imageData = static function (?string $path): string {
        if (!$path || !preg_match('#^uploads/[A-Za-z0-9_.-]+$#', $path)) return '';
        $file = dirname(__DIR__).'/'.$path;
        if (!is_file($file)) return '';
        $mime = mime_content_type($file);
        if (!in_array($mime, ['image/png', 'image/jpeg'], true)) return '';
        return 'data:'.$mime.';base64,'.base64_encode((string)file_get_contents($file));
    };
    $sealData = $imageData($parish['seal_image_path'] ?? null);
    $signatureData = $imageData($parish['priest_signature_path'] ?? null);

    $body = match ($r['sacrament']) {
        'Marriage' => "This is to certify that <strong>{$esc($name)}</strong> and <strong>{$esc($r['spouse'])}</strong> were joined in Holy Matrimony according to the rites of the Roman Catholic Church on <strong>{$date($r['eventDate'])}</strong>, as attested by the Sacramental Registers of this Parish.",
        'Death' => "This is to certify that <strong>{$esc($name)}</strong>, born on <strong>{$date($r['dob'])}</strong> to <strong>{$esc($r['fatherName'])}</strong> and <strong>{$esc($r['motherName'])}</strong>, departed this life on <strong>{$date($r['eventDate'])}</strong> and was given ecclesiastical rites according to the Roman Catholic Church.",
        default => "This is to certify that <strong>{$esc($name)}</strong>, born on <strong>{$date($r['dob'])}</strong> to <strong>{$esc($r['fatherName'])}</strong> and <strong>{$esc($r['motherName'])}</strong>, received the Sacrament of <strong>{$esc($r['sacrament'])}</strong> in this Parish on <strong>{$date($r['eventDate'])}</strong>, according to the Rites of the Roman Catholic Church.",
    };
    // The template is editable text: substitute values then escape all markup.
    $body = $esc(strtr((string)($template['body_template'] ?? ''), $tokens));
    $title = (string)($template['title_text'] ?? $title);
    $footerNote = $esc((string)($template['footer_note'] ?? 'Not valid without the parish dry seal.'));
    $seal = $sealData ? '<img class="seal-image" src="'.$sealData.'" alt="Parish seal" />' : '<div class="seal"><table><tr><td>PARISH<br/>SEAL</td></tr></table></div>';
    $signature = $signatureData ? '<img class="signature" src="'.$signatureData.'" alt="Priest signature" />' : '<div class="signature-space"></div>';

    $marginNotes = !empty($r['marginNotes']) ? "
        <div style='background:#f3e0e3;border-left:3px solid #7a2734;padding:10px 14px;margin:0 0 24px;'>
            <span style='display:block;font-size:11px;font-weight:600;color:#5e1f29;'>CANONICAL MARGIN NOTES</span>
            <span style='color:#3a1015;'>{$esc($r['marginNotes'])}</span>
        </div>" : '';

    return <<<HTML
    <html><head><style>
        @page { margin: 0; }
        body { font-family: 'Times New Roman', serif; color:#1c2431; margin:0; }
        .wrap { padding: 56px 58px; }
        .eyebrow { text-align:center; font-size:12px; letter-spacing:1px; color:#7a2734; margin:0; }
        .parish { text-align:center; font-size:26px; font-weight:bold; margin:5px 0; }
        .sub { text-align:center; font-size:12px; color:#64738c; margin:0 0 18px; }
        .rule { border-top:2px solid #a9812f; margin-bottom:20px; }
        .seal { width:90px; height:90px; border-radius:50%; background:#a9812f; border:3px solid #8a6a24;
                margin:0 auto 22px; color:#3a2c0c; font-size:11px; font-weight:bold; }
        .seal table { width:84px; height:84px; border-collapse:collapse; }
        .seal td { text-align:center; vertical-align:middle; line-height:12px; }
        .seal-image { display:block; width:96px; height:96px; margin:0 auto 18px; object-fit:contain; }
        .title { text-align:center; font-size:22px; font-weight:bold; letter-spacing:.5px; margin-bottom:24px; }
        .body { font-size:16px; line-height:1.9; text-align:justify; margin-bottom:26px; }
        .refstrip { border-top:1px solid #d8d2c2; border-bottom:1px solid #d8d2c2; padding:12px 0; margin-bottom:24px; width:100%; }
        .refstrip td { font-size:14px; padding:0 10px; }
        .reflabel { display:block; font-size:11px; color:#64738c; }
        .sigrow td { font-size:14px; padding-top:30px; }
        .sigline { border-top:1px solid #151b2b; width:200px; margin-bottom:6px; }
        .signature, .signature-space { display:block; width:200px; height:48px; object-fit:contain; object-position:left bottom; }
        .footer { text-align:center; font-size:12px; color:#64738c; border-top:1px dashed #c3cbd9; padding-top:14px; margin-top:40px; }
    </style></head><body>
    <div class="wrap">
        <p class="eyebrow">{$esc($parish['diocese_name'] ?? '')}</p>
        <p class="parish">{$esc($parish['parish_name'] ?? '')}</p>
        <p class="sub">{$esc($parish['address'] ?? '')}</p>
        <div class="rule"></div>
        {$seal}
        <p class="title">{$esc($title)}</p>
        <p class="body">{$body}</p>
        <table class="refstrip"><tr>
            <td><span class="reflabel">Book No.</span><strong>{$esc($r['book'])}</strong></td>
            <td><span class="reflabel">Page No.</span><strong>{$esc($r['page'])}</strong></td>
            <td><span class="reflabel">Line Entry No.</span><strong>{$esc($r['line'])}</strong></td>
        </tr></table>
        {$marginNotes}
        <table width="100%" class="sigrow"><tr>
            <td width="50%">
                {$signature}
                <div class="sigline"></div>
                <strong>{$esc($priest)}</strong><br/>
                <span style="font-size:11px;color:#64738c;">Officiating Priest / Parish Priest</span>
            </td>
            <td width="50%" align="right">
                <div class="sigline" style="margin-left:auto;"></div>
                <span style="font-size:11px;color:#64738c;">Date Issued</span><br/>
                <strong>{$today}</strong>
            </td>
        </tr></table>
        <p class="footer">Issued to <strong>{$issuedTo}</strong> for the purpose of <strong>{$esc($purpose)}</strong>. {$footerNote}</p>
    </div>
    </body></html>
    HTML;
}
