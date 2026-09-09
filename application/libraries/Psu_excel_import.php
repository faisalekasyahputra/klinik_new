<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Psu_excel_import
{
    const BATAS = 1000;
    const HEADER = ['nama_perumahan','nama_pengembang','kabupaten_kota','kode_asosiasi','status_serah_terima','tanggal_serah_terima','pengembang_srp2_id','keterangan','tampil_di_publik'];

    public function baca($path, array $kabupaten, array $asosiasi, array $pengembang)
    {
        try {
            $reader=IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(FALSE);
            $book=$reader->load($path);
        } catch (\Throwable $e) {
            log_message('error','Psu_excel_import: '.$e->getMessage());
            return $this->gagal('Berkas tidak dapat dibaca. Gunakan template XLSX/XLS yang asli.');
        }
        try {
            $sheet=$book->getSheetByName('Data PSU');
            if(!$sheet) return $this->gagal('Sheet "Data PSU" tidak ditemukan.');
            $last=(int)$sheet->getHighestDataRow();
            if($last>5000) return $this->gagal('Sheet terlalu panjang. Hapus baris atau format kosong di bagian bawah.');
            list($header,$col)=$this->header($sheet,$last);
            if($header===NULL) return $this->gagal('Judul kolom tidak lengkap. Jangan mengubah baris judul template.');

            $kabId=$kabNama=$aso=$dev=[];
            foreach($kabupaten as $x){$kabId[(string)(int)$x->id]=(int)$x->id;$kabNama[$this->norm($x->nama)]=(int)$x->id;}
            foreach($asosiasi as $k=>$v){$aso[$this->norm($k)]=(string)$k;$aso[$this->norm($v)]=(string)$k;}
            foreach($pengembang as $x)$dev[(string)(int)$x->id]=(int)$x->id;
            $statuses=['belum_diserahkan'=>'belum_diserahkan','belum diserahkan'=>'belum_diserahkan','proses_verifikasi'=>'proses_verifikasi','proses verifikasi'=>'proses_verifikasi','sudah_diserahkan'=>'sudah_diserahkan','sudah diserahkan'=>'sudah_diserahkan'];
            $public=['ya'=>1,'yes'=>1,'1'=>1,'true'=>1,'tidak'=>0,'no'=>0,'0'=>0,'false'=>0];
            $rows=$errors=$seen=[];$filled=0;

            for($r=$header+1;$r<=$last;$r++){
                $raw=[];$formula=FALSE;
                foreach(self::HEADER as $name){
                    $cell=$sheet->getCellByColumnAndRow($col[$name],$r);
                    $formula=$formula||$cell->getDataType()===DataType::TYPE_FORMULA;
                    $raw[$name]=$cell->getValue();
                }
                if($this->emptyRow($raw))continue;
                if(++$filled>self::BATAS)return $this->gagal('Maksimal '.self::BATAS.' baris data per unggahan.');
                if($formula){$errors[]="Baris {$r}: formula tidak diizinkan.";continue;}

                $nama=trim((string)$raw['nama_perumahan']);$peng=trim((string)$raw['nama_pengembang']);$ket=trim((string)$raw['keterangan']);
                if($nama===''||mb_strlen($nama)>180)$errors[]="Baris {$r}: nama_perumahan wajib, maksimal 180 karakter.";
                if($peng===''||mb_strlen($peng)>180)$errors[]="Baris {$r}: nama_pengembang wajib, maksimal 180 karakter.";
                if(mb_strlen($ket)>255)$errors[]="Baris {$r}: keterangan maksimal 255 karakter.";

                $kid=NULL;$kt=trim((string)$raw['kabupaten_kota']);
                if($kt!==''){
                    $code=preg_match('/^\s*(\d+)\s*-/', $kt,$m)?(string)(int)$m[1]:'';
                    if($code!==''&&isset($kabId[$code]))$kid=$kabId[$code];
                    elseif(isset($kabNama[$this->norm($kt)]))$kid=$kabNama[$this->norm($kt)];
                    else $errors[]="Baris {$r}: kabupaten_kota tidak dikenal.";
                }
                $ak=NULL;$at=$this->norm($raw['kode_asosiasi']);
                if($at!==''){if(isset($aso[$at]))$ak=$aso[$at];else $errors[]="Baris {$r}: kode_asosiasi tidak dikenal.";}
                $st=$statuses[$this->norm($raw['status_serah_terima'])]??NULL;
                if($st===NULL)$errors[]="Baris {$r}: status_serah_terima tidak valid.";
                $pid=NULL;$pt=trim((string)$raw['pengembang_srp2_id']);
                if($pt!==''){
                    $pk=ctype_digit($pt)?(string)(int)$pt:'';
                    if($pk!==''&&isset($dev[$pk]))$pid=$dev[$pk];else $errors[]="Baris {$r}: pengembang_srp2_id tidak aktif/ditemukan.";
                }
                $date=$this->date($raw['tanggal_serah_terima']);
                if($date===FALSE){$errors[]="Baris {$r}: tanggal harus berupa tanggal Excel atau YYYY-MM-DD.";$date=NULL;}
                $pub=$this->norm($raw['tampil_di_publik']);
                if(!array_key_exists($pub,$public)){$errors[]="Baris {$r}: tampil_di_publik wajib Ya atau Tidak.";$active=0;}else $active=$public[$pub];
                $key=$this->kunci_duplikat($nama,$kid);
                if($nama!==''&&isset($seen[$key]))$errors[]="Baris {$r}: duplikat dengan baris {$seen[$key]}.";
                else $seen[$key]=$r;
                $rows[]=['nama_perumahan'=>$nama,'nama_pengembang'=>$peng,'pengembang_id'=>$pid,'asosiasi'=>$ak,'kabupaten_id'=>$kid,'status_serah_terima'=>$st,'tanggal_serah_terima'=>$date,'keterangan'=>$ket===''?NULL:$ket,'status_aktif'=>$active];
            }
            if($errors){$show=array_slice($errors,0,12);$more=count($errors)-count($show);return $this->gagal(implode(' ',$show).($more?" Masih ada {$more} kesalahan lain.":'').' Tidak ada data yang disimpan.');}
            if(!$rows)return $this->gagal('Tidak ada baris data pada sheet "Data PSU".');
            return ['success'=>TRUE,'message'=>'','rows'=>$rows];
        } finally {$book->disconnectWorksheets();unset($book);}
    }

    public function kunci_duplikat($nama,$kabupaten_id){return $this->norm($nama).'|'.(int)$kabupaten_id;}
    private function header($sheet,$last){
        $max=min(50,Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
        for($r=1;$r<=min(10,$last);$r++){$found=[];for($c=1;$c<=$max;$c++){$n=$this->norm($sheet->getCellByColumnAndRow($c,$r)->getValue());if(in_array($n,self::HEADER,TRUE))$found[$n]=$c;}if(count($found)===count(self::HEADER))return [$r,$found];}
        return [NULL,[]];
    }
    private function emptyRow($row){foreach($row as $v)if(trim((string)$v)!=='')return FALSE;return TRUE;}
    private function date($v){
        if($v===NULL||trim((string)$v)==='')return NULL;
        if($v instanceof \DateTimeInterface)return $v->format('Y-m-d');
        if(is_numeric($v)){try{return ExcelDate::excelToDateTimeObject((float)$v)->format('Y-m-d');}catch(\Throwable $e){return FALSE;}}
        $s=trim((string)$v);$d=\DateTime::createFromFormat('!Y-m-d',$s);$e=\DateTime::getLastErrors();
        return $d&&($e===FALSE||(!$e['warning_count']&&!$e['error_count']))&&$d->format('Y-m-d')===$s?$s:FALSE;
    }
    private function norm($v){return mb_strtolower(trim((string)$v),'UTF-8');}
    private function gagal($m){return ['success'=>FALSE,'message'=>$m,'rows'=>[]];}
}
