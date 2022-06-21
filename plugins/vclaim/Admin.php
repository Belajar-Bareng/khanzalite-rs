<?php
namespace Plugins\Vclaim;

use Systems\AdminModule;
use Systems\Lib\BpjsService;
use Systems\Lib\QRCode;

class Admin extends AdminModule
{

    public function init()
    {
      $this->consid = $this->settings->get('settings.BpjsConsID');
      $this->secretkey = $this->settings->get('settings.BpjsSecretKey');
      $this->user_key = $this->settings->get('settings.BpjsUserKey');
      $this->api_url = $this->settings->get('settings.BpjsApiUrl');
      $this->vclaim_version = $this->settings->get('settings.vClaimVersion');
    }

    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
            'Referensi' => 'referensi',
            'Peserta' => 'peserta',
            'Rencana Kontrol' => 'rencanakontrol',
            'SEP' => 'sep',
            'Rujukan' => 'rujukan',
            'PRB' => 'prb',
            'Lembar Pengajuan Klaim' => 'lpk',
            'Monitoring' => 'monitoring'
        ];
    }

    public function getManage()
    {
        $parsedown = new \Systems\Lib\Parsedown();
        $readme_file = MODULES.'/vclaim/ReadMe.md';
        $readme =  $parsedown->text($this->tpl->noParse(file_get_contents($readme_file)));
        return $this->draw('manage.html', ['readme' => $readme]);
    }

    public function getReferensi()
    {
        return $this->draw('referensi.html');
    }

    public function getPeserta()
    {
        $this->_addHeaderFiles();
        return $this->draw('peserta.html');
    }

    public function getSEP()
    {
        $this->_addHeaderFiles();
        return $this->draw('sep.html');
    }

    public function getPRB()
    {
        $this->_addHeaderFiles();
        return $this->draw('prb.html');
    }

    public function getRujukan()
    {
        $this->_addHeaderFiles();
        return $this->draw('rujukan.html');
    }

    public function getRencanaKontrol()
    {
        $this->_addHeaderFiles();
        return $this->draw('rencana.kontrol.html');
    }

    public function getLPK()
    {
        return $this->draw('lpk.html');
    }

    public function getMonitoring()
    {
        return $this->draw('monitoring.html');
    }

    public function postSaveSEP()
    {

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $_POST['kdppkpelayanan'] = $this->settings->get('settings.ppk_bpjs');
        $_POST['nmppkpelayanan'] = $this->settings->get('settings.nama_instansi');
        $_POST['sep_user']	= $this->core->getUserInfo('fullname', null, true);

        $data = [
            'request' => [
               't_sep' => [
                  'noKartu' => $_POST['no_kartu'],
                  'tglSep' => $_POST['tglsep'],
                  'ppkPelayanan' => $_POST['kdppkpelayanan'],
                  'jnsPelayanan' => $_POST['jnspelayanan'],
                  'klsRawat' => [
                     'klsRawatHak' => $_POST['klsrawat'],
                     'klsRawatNaik' => '',
                     'pembiayaan' => '',
                     'penanggungJawab' => ''
                  ],
                  'noMR' => $_POST['nomr'],
                  'rujukan' => [
                     'asalRujukan' => $_POST['asal_rujukan'],
                     'tglRujukan' => $_POST['tglrujukan'],
                     'noRujukan' => $_POST['norujukan'],
                     'ppkRujukan' => $_POST['kdppkrujukan']
                  ],
                  'catatan' => $_POST['catatan'],
                  'diagAwal' => $_POST['diagawal'],
                  'poli' => [
                     'tujuan' => $_POST['kdpolitujuan'],
                     'eksekutif' => $_POST['eksekutif']
                  ],
                  'cob' => [
                     'cob' => $_POST['cob']
                  ],
                  'katarak' => [
                     'katarak' => $_POST['katarak']
                  ],
                  'jaminan' => [
                     'lakaLantas' => $_POST['lakalantas'],
                     'noLP' => $_POST['noLp'],
                     'penjamin' => [
                         'tglKejadian' => $_POST['tglkkl'],
                         'keterangan' => $_POST['keterangankkl'],
                         'suplesi' => [
                             'suplesi' => $_POST['suplesi'],
                             'noSepSuplesi' => $_POST['no_sep_suplesi'],
                             'lokasiLaka' => [
                                 'kdPropinsi' => $_POST['kdprop'],
                                 'kdKabupaten' => $_POST['kdkab'],
                                 'kdKecamatan' => $_POST['kdkec']
                             ]
                         ]
                     ]
                  ],
                  'tujuanKunj' => $_POST['tujuanKunj'],
                  'flagProcedure' => $_POST['flagProcedure'],
                  'kdPenunjang' => $_POST['kdPenunjang'],
                  'assesmentPel' => $_POST['assesmentPel'],
                  'skdp' => [
                     'noSurat' => $_POST['noskdp'],
                     'kodeDPJP' => $_POST['kddpjp']
                  ],
                  'dpjpLayan' => $_POST['kddpjppelayanan'],
                  'noTelp' => $_POST['notelep'],
                  'user' => $_POST['sep_user']
               ]
            ]
        ];

        $data = json_encode($data);

        $url = $this->api_url.'SEP/2.0/insert';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $data = json_decode($output, true);


        if($data == NULL) {

          echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';

        } else if($data['metaData']['code'] == 200){

          $code = $data['metaData']['code'];
          $message = $data['metaData']['message'];
          if($this->vclaim_version == 1) {
            echo json_encode($json);
          } else {
            $stringDecrypt = stringDecrypt($key, $data['response']);
            $decompress = '""';
            if(!empty($stringDecrypt)) {
              $decompress = decompress($stringDecrypt);
            }
           if($data != null) {
              $data = '{
              	"metaData": {
              		"code": "'.$code.'",
              		"message": "'.$message.'"
              	},
              	"response": '.$decompress.'}';

              $data = json_decode($data, true);

              $_POST['sep_no_sep'] = $data['response']['sep']['noSep'];

              if($_POST['jnspelayanan'] == 1) {
                $this->db('bridging_surat_pri_bpjs')->where('no_surat', $_POST['noskdp'])->update([
                  'no_sep' => $_POST['sep_no_sep']
                ]);
              }

              $simpan_sep = $this->db('bridging_sep')->save([
                'no_sep' => $_POST['sep_no_sep'],
                'no_rawat' => $_POST['no_rawat'],
                'tglsep' => $_POST['tglsep'],
                'tglrujukan' => $_POST['tglrujukan'],
                'no_rujukan' => $_POST['norujukan'],
                'kdppkrujukan' => $_POST['kdppkrujukan'],
                'nmppkrujukan' => $_POST['nmppkrujukan'],
                'kdppkpelayanan' => $_POST['kdppkpelayanan'],
                'nmppkpelayanan' => $_POST['nmppkpelayanan'],
                'jnspelayanan' => $_POST['jnspelayanan'],
                'catatan' => $_POST['catatan'],
                'diagawal' => $_POST['diagawal'],
                'nmdiagnosaawal' => $_POST['nmdiagnosaawal'],
                'kdpolitujuan' => $_POST['kdpolitujuan'],
                'nmpolitujuan' => $_POST['nmpolitujuan'],
                'klsrawat' => $_POST['klsrawat'],
                'klsnaik' => $_POST['klsnaik'],
                'pembiayaan' => $_POST['pembiayaan'],
                'pjnaikkelas' => $_POST['pjnaikkelas'],
                'lakalantas' => $_POST['lakalantas'],
                'user' => $_POST['sep_user'],
                'nomr' => $_POST['nomr'],
                'nama_pasien' => $_POST['nama_pasien'],
                'tanggal_lahir' => $_POST['tanggal_lahir'],
                'peserta' => $_POST['peserta'],
                'jkel' => $_POST['jenis_kelamin'],
                'no_kartu' => $_POST['no_kartu'],
                'tglpulang' => $_POST['tglpulang'],
                'asal_rujukan' => $_POST['asal_rujukan'],
                'eksekutif' => $_POST['eksekutif'],
                'cob' => $_POST['cob'],
                'notelep' => $_POST['notelep'],
                'katarak' => $_POST['katarak'],
                'tglkkl' => $_POST['tglkkl'],
                'keterangankkl' => $_POST['keterangankkl'],
                'suplesi' => $_POST['suplesi'],
                'no_sep_suplesi' => $_POST['no_sep_suplesi'],
                'kdprop' => $_POST['kdprop'],
                'nmprop' => $_POST['nmprop'],
                'kdkab' => $_POST['kdkab'],
                'nmkab' => $_POST['nmkab'],
                'kdkec' => $_POST['kdkec'],
                'nmkec' => $_POST['nmkec'],
                'noskdp' => $_POST['noskdp'],
                'kddpjp' => $_POST['kddpjp'],
                'nmdpdjp' => $_POST['nmdpdjp'],
                'tujuankunjungan' => $_POST['tujuanKunj'],
                'flagprosedur' => $_POST['flagProcedure'],
                'penunjang' => $_POST['kdPenunjang'],
                'asesmenpelayanan' => $_POST['assesmentPel'],
                'kddpjplayanan' => $_POST['kddpjppelayanan'],
                'nmdpjplayanan' => $_POST['nmdpjppelayanan']
              ]);

              if($simpan_sep) {
                if($_POST['prolanis_prb'] !=='') {
                  $simpan_prb = $this->db('bpjs_prb')->save([
                    'no_sep' => $_POST['sep_no_sep'],
                    'prb' => $_POST['prolanis_prb']
                  ]);
                }
                echo $_POST['sep_no_sep'];
              } else {
                $simpan_sep = $this->db('bridging_sep_internal')->save([
                  'no_sep' => $_POST['sep_no_sep'],
                  'no_rawat' => $_POST['no_rawat'],
                  'tglsep' => $_POST['tglsep'],
                  'tglrujukan' => $_POST['tglrujukan'],
                  'no_rujukan' => $_POST['norujukan'],
                  'kdppkrujukan' => $_POST['kdppkrujukan'],
                  'nmppkrujukan' => $_POST['nmppkrujukan'],
                  'kdppkpelayanan' => $_POST['kdppkpelayanan'],
                  'nmppkpelayanan' => $_POST['nmppkpelayanan'],
                  'jnspelayanan' => $_POST['jnspelayanan'],
                  'catatan' => $_POST['catatan'],
                  'diagawal' => $_POST['diagawal'],
                  'nmdiagnosaawal' => $_POST['nmdiagnosaawal'],
                  'kdpolitujuan' => $_POST['kdpolitujuan'],
                  'nmpolitujuan' => $_POST['nmpolitujuan'],
                  'klsrawat' => $_POST['klsrawat'],
                  'klsnaik' => $_POST['klsnaik'],
                  'pembiayaan' => $_POST['pembiayaan'],
                  'pjnaikkelas' => $_POST['pjnaikkelas'],
                  'lakalantas' => $_POST['lakalantas'],
                  'user' => $_POST['sep_user'],
                  'nomr' => $_POST['nomr'],
                  'nama_pasien' => $_POST['nama_pasien'],
                  'tanggal_lahir' => $_POST['tanggal_lahir'],
                  'peserta' => $_POST['peserta'],
                  'jkel' => $_POST['jenis_kelamin'],
                  'no_kartu' => $_POST['no_kartu'],
                  'tglpulang' => $_POST['tglpulang'],
                  'asal_rujukan' => $_POST['asal_rujukan'],
                  'eksekutif' => $_POST['eksekutif'],
                  'cob' => $_POST['cob'],
                  'notelep' => $_POST['notelep'],
                  'katarak' => $_POST['katarak'],
                  'tglkkl' => $_POST['tglkkl'],
                  'keterangankkl' => $_POST['keterangankkl'],
                  'suplesi' => $_POST['suplesi'],
                  'no_sep_suplesi' => $_POST['no_sep_suplesi'],
                  'kdprop' => $_POST['kdprop'],
                  'nmprop' => $_POST['nmprop'],
                  'kdkab' => $_POST['kdkab'],
                  'nmkab' => $_POST['nmkab'],
                  'kdkec' => $_POST['kdkec'],
                  'nmkec' => $_POST['nmkec'],
                  'noskdp' => $_POST['noskdp'],
                  'kddpjp' => $_POST['kddpjp'],
                  'nmdpdjp' => $_POST['nmdpdjp'],
                  'tujuankunjungan' => $_POST['tujuanKunj'],
                  'flagprosedur' => $_POST['flagProcedure'],
                  'penunjang' => $_POST['kdPenunjang'],
                  'asesmenpelayanan' => $_POST['assesmentPel'],
                  'kddpjplayanan' => $_POST['kddpjppelayanan'],
                  'nmdpjplayanan' => $_POST['nmdpjppelayanan']
                ]);
                if($_POST['prolanis_prb'] !=='') {
                  $simpan_prb = $this->db('bpjs_prb')->save([
                    'no_sep' => $_POST['sep_no_sep'],
                    'prb' => $_POST['prolanis_prb']
                  ]);
                }
                echo $_POST['sep_no_sep'];
              }
            } else {
              echo '{
              	"metaData": {
              		"code": "5000",
              		"message": "ERROR"
              	},
              	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
            }
          }
        } else {

          echo $data['metaData']['message'];

        }

        exit();
    }

    public function postHapusSEP()
    {
        $_POST['sep_user']	= $this->core->getUserInfo('fullname', null, true);

        $data = [
            'request' => [
               't_sep' => [
                  'noSep' => $_POST['no_sep'],
                  'user' => $_POST['sep_user']
               ]
            ]
        ];

        $data = json_encode($data);

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'SEP/Delete';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $data = json_decode($output, true);

        if($data == NULL) {

          echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';

        } else if($data['metaData']['code'] == 200){
          $hapus_sep = $this->db('bridging_sep')->where('no_sep', $_POST['no_sep'])->delete();
          $hapus_sep_internal = $this->db('bridging_sep_internal')->where('no_sep', $_POST['no_sep'])->delete();
          $hapus_prb = $this->db('bpjs_prb')->where('no_sep', $_POST['no_sep'])->delete();
          echo $data['metaData']['message'].'!! Menghapus data SEP dengan nomor '.$_POST['no_sep'].'....';
        } else {
          echo $data['metaData']['message'];
        }
        exit();
    }

    public function getCetakSEP($no_sep)
    {
        $settings = $this->settings('settings');
        $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
        $data_sep = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
        if(!$data_sep) {
          $data_sep = $this->db('bridging_sep_internal')->where('no_sep', $no_sep)->oneArray();
        }
        $batas_rujukan = strtotime('+87 days', strtotime($data_sep['tglrujukan']));

        $qr=QRCode::getMinimumQRCode($data_sep['no_sep'],QR_ERROR_CORRECT_LEVEL_L);
        //$qr=QRCode::getMinimumQRCode('Petugas: '.$this->core->getUserInfo('fullname', null, true).'; Lokasi: '.UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf',QR_ERROR_CORRECT_LEVEL_L);
        $im=$qr->createImage(4,4);
        imagepng($im,'/admin/tmp/qrcode.png');
        imagedestroy($im);

        $image = "/admin/tmp/qrcode.png";

        $data_sep['qrCode'] = $image;
        $data_sep['batas_rujukan'] = date('Y-m-d', $batas_rujukan);
        $potensi_prb = $this->db('bpjs_prb')->where('no_sep', $no_sep)->oneArray();
        $data_sep['potensi_prb'] = $potensi_prb['prb'];

        echo $this->draw('cetak.sep.html', ['data_sep' => $data_sep]);
        exit();
    }

    public function postSyncSEP()
    {
        $_POST['kdppkpelayanan'] = $this->settings->get('settings.ppk_bpjs');
        $_POST['nmppkpelayanan'] = $this->settings->get('settings.nama_instansi');
        $_POST['sep_user']	= $this->core->getUserInfo('fullname', null, true);

        $data = $this->db('reg_periksa')
          ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
          ->where('no_peserta', $_POST['no_kartu'])
          ->where('tgl_registrasi', $_POST['tglsep'])
          ->oneArray();

        if(!$data) {

          echo 'Data pasien tidak ditemukan!';

        } else {

          /*$simpan_sep = $this->db('bridging_sep')->save([
            'no_sep' => $_POST['noSep'],
            'no_rawat' => $data['no_rawat'],
            'tglsep' => $_POST['tglsep'],
            'tglrujukan' => $_POST['tglRujukan'],
            'no_rujukan' => $_POST['norujukan'],
            'kdppkrujukan' => $_POST['kdppkrujukan'],
            'nmppkrujukan' => $_POST['nmppkrujukan'],
            'kdppkpelayanan' => $_POST['kdppkpelayanan'],
            'nmppkpelayanan' => $_POST['nmppkpelayanan'],
            'jnspelayanan' => $_POST['jnspelayanan'],
            'catatan' => $_POST['catatan'],
            'diagawal' => $_POST['diagawal'],
            'nmdiagnosaawal' => $_POST['nmdiagnosaawal'],
            'kdpolitujuan' => $_POST['kdpolitujuan'],
            'nmpolitujuan' => $_POST['nmpolitujuan'],
            'klsrawat' => $_POST['klsrawat'],
            'lakalantas' => $_POST['lakalantas'],
            'user' => $_POST['sep_user'],
            'nomr' => $_POST['nomr'],
            'nama_pasien' => $_POST['nama_pasien'],
            'tanggal_lahir' => $_POST['tanggal_lahir'],
            'peserta' => $_POST['peserta'],
            'jkel' => $_POST['jenis_kelamin'],
            'no_kartu' => $_POST['no_kartu'],
            'tglpulang' => $_POST['tglpulang'],
            'asal_rujukan' => $_POST['asal_rujukan'],
            'eksekutif' => $_POST['eksekutif'],
            'cob' => $_POST['cob'],
            'penjamin' => $_POST['penjamin'],
            'notelep' => $_POST['notelep'],
            'katarak' => $_POST['katarak'],
            'tglkkl' => $_POST['tglkkl'],
            'keterangankkl' => $_POST['keterangankkl'],
            'suplesi' => $_POST['suplesi'],
            'no_sep_suplesi' => $_POST['no_sep_suplesi'],
            'kdprop' => $_POST['kdprop'],
            'nmprop' => $_POST['nmprop'],
            'kdkab' => $_POST['kdkab'],
            'nmkab' => $_POST['nmkab'],
            'kdkec' => $_POST['kdkec'],
            'nmkec' => $_POST['nmkec'],
            'noskdp' => $_POST['noskdp'],
            'kddpjp' => $_POST['kddpjp'],
            'nmdpdjp' => $_POST['nmdpdjp']
          ]);
          $simpan_prb = $this->db('bpjs_prb')->save([
            'no_sep' => $_POST['sep_no_sep'],
            'prb' => $_POST['prolanis_prb']
          ]);

          if($simpan_sep) {
            echo $_POST['sep_no_sep'];
          }*/

          echo '0186R0020920V003231';

        }

        exit();

    }

    public function getCariByNoRujukanModal($searchBy, $keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        if ($searchBy == 'RS') {
            $url = 'Rujukan/RS/'.$keyword;
        } else {
            $url = 'Rujukan/'.$keyword;
        }
        $url = $this->api_url.''.$url;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
         if($json != null) {
            $json = '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
            $this->tpl->set('rujukan', json_encode($json, JSON_PRETTY_PRINT));
            echo $this->tpl->draw(MODULES.'/vclaim/view/admin/rujukan.modal.html', true);
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDiagnosa($keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/diagnosa/'.$keyword;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
         if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }
    public function getPoli($keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/poli/'.$keyword;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
            if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }
    public function getFaskes($kd_faskes = null, $jns_faskes = null)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/faskes/'.$kd_faskes.'/'.$jns_faskes;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDokterDpjp($jnsPelayanan, $tglPelayanan, $spesialis)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        $url = $this->api_url.'referensi/dokter/pelayanan/'.$jnsPelayanan.'/tglPelayanan/'.$tglPelayanan.'/Spesialis/'.$spesialis;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getPropinsi()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/propinsi';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getKabupaten($kdPropinsi)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/kabupaten/propinsi/'.$kdPropinsi;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getKecamatan($kdKabupaten)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/kecamatan/kabupaten/'.$kdKabupaten;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getProcedure($keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/procedure/'.$keyword;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getKelasRawat()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/kelasrawat';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDokter($keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/dokter/'.$keyword;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getSpesialistik()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/spesialistik';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getRuangRawat()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/ruangrawat';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCaraKeluar()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/carakeluar';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getPascaPulang()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'referensi/pascapulang';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getByNoKartu($noKartu, $tglPelayananSEP)
    {
      date_default_timezone_set('UTC');
      $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
      $key = $this->consid.$this->secretkey.$tStamp;

      $url = $this->api_url.'Peserta/nokartu/'.$noKartu.'/tglSEP/'.$tglPelayananSEP;
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $json = json_decode($output, true);
      //echo json_encode($json);
      $code = $json['metaData']['code'];
      $message = $json['metaData']['message'];
      if($this->vclaim_version == 1) {
        echo json_encode($json);
      } else {
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
        if($json != null) {
          echo '{
            "metaData": {
              "code": "'.$code.'",
              "message": "'.$message.'"
            },
            "response": '.$decompress.'}';
        } else {
          echo '{
            "metaData": {
              "code": "5000",
              "message": "ERROR"
            },
            "response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
        }
      }
      exit();
    }

    public function getByNIK($nik, $tglPelayananSEP)
    {
      date_default_timezone_set('UTC');
      $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
      $key = $this->consid.$this->secretkey.$tStamp;

      $url = $this->api_url.'Peserta/nik/'.$nik.'/tglSEP/'.$tglPelayananSEP;
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $json = json_decode($output, true);
      //echo json_encode($json);
      $code = $json['metaData']['code'];
      $message = $json['metaData']['message'];
      if($this->vclaim_version == 1) {
        echo json_encode($json);
      } else {
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
        if($json != null) {
          echo '{
          	"metaData": {
          		"code": "'.$code.'",
          		"message": "'.$message.'"
          	},
          	"response": '.$decompress.'}';
        } else {
          echo '{
          	"metaData": {
          		"code": "5000",
          		"message": "ERROR"
          	},
          	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
        }
      }
      exit();
    }

    public function postInsertSEP($data = [])
    {
        $data = [
            'request' => [
               't_sep' => [
                  'noKartu' => '0001112230666',
                  'tglSep' => '2017-10-18',
                  'ppkPelayanan' => '0301R001',
                  'jnsPelayanan' => '2',
                  'klsRawat' => '3',
                  'noMR' => '123456',
                  'rujukan' => [
                     'asalRujukan' => '1',
                     'tglRujukan' => '2017-10-17',
                     'noRujukan' => '1234567',
                     'ppkRujukan' => '00010001'
                  ],
                  'catatan' => 'test',
                  'diagAwal' => 'A00.1',
                  'poli' => [
                     'tujuan' => 'INT',
                     'eksekutif' => '0'
                  ],
                  'cob' => [
                     'cob' => '0'
                  ],
                  'katarak' => [
                     'katarak' => '0'
                  ],
                  'jaminan' => [
                     'lakaLantas' => '0',
                     'penjamin' => [
                         'penjamin' => '1',
                         'tglKejadian' => '2018-08-06',
                         'keterangan' => 'kll',
                         'suplesi' => [
                             'suplesi' => '0',
                             'noSepSuplesi' => '0301R0010718V000001',
                             'lokasiLaka' => [
                                 'kdPropinsi' => '03',
                                 'kdKabupaten' => '0050',
                                 'kdKecamatan' => '0574'
                             ]
                         ]
                     ]
                  ],
                  'skdp' => [
                     'noSurat' => '000002',
                     'kodeDPJP' => '31661'
                  ],
                  'noTelp' => '081919999',
                  'user' => 'Coba Ws'
               ]
            ]
        ];

        $data = json_encode($data);

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'SEP/2.0/insert';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        echo json_encode($json);
        exit();
    }

    public function postUpdateSEP($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'SEP/2.0/Update';
        $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDeleteSEP($sep)
    {
        $data = [
            'request' => [
                't_sep' => [
                    'noSep' => $sep,
                    'user' => 'admin'
                ]
            ]
        ];

        $data = json_encode($data);

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'SEP/Delete';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCariSEP($keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'SEP/'.$keyword;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getSuplesiJasaRaharja($noKartu, $tglPelayanan)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'sep/JasaRaharja/Suplesi/'.$noKartu.'/tglPelayanan/'.$tglPelayanan;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }
    public function postPengajuanPenjaminanSep($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Sep/pengajuanSEP';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }
    public function postApprovalPenjaminanSep($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Sep/aprovalSEP';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }
    public function postUpdateTglPlg($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Sep/updtglplg';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey,  $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getInacbgSEP($keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'sep/cbg/'.$keyword;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function postInsertRujukan($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Rujukan/insert';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function postUpdateRujukan($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Rujukan/update';
        $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function postDeleteRujukan($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Rujukan/delete';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCariByNoRujukan($searchBy, $keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        if ($searchBy == 'RS') {
            $url = 'Rujukan/RS/'.$keyword;
        } else {
            $url = 'Rujukan/'.$keyword;
        }
        $url = $this->api_url.''.$url;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        $jsonresponse = $json['response'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
              "response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCariByNoKartu($searchBy, $keyword, $multi = false)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $record = $multi ? 'List/' : '';
        if ($searchBy == 'RS') {
            $url = 'Rujukan/RS/'.$record.'Peserta/'.$keyword;
        } else {
            $url = 'Rujukan/'.$record.'Peserta/'.$keyword;
        }
        $url = $this->api_url.''.$url;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCariByTglRujukan($searchBy, $keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        if ($searchBy == 'RS') {
            $url = 'Rujukan/RS/List/TglRujukan/'.$keyword;
        } else {
            $url = 'Rujukan/List/TglRujukan/'.$keyword;
        }
        $url = $this->api_url.''.$url;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);

        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';

            $noKunjungan = [];
            $i = 1;
            foreach ($json['response']['rujukan'] as $key=>$value) {
              //$keyword = $value['noKunjungan'];
              /*if ($searchBy == 'RS') {
                  $url = 'Rujukan/RS/'.$keyword;
              } else {
                  $url = 'Rujukan/'.$keyword;
              }
              $url = $this->api_url.''.$url;
              $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
              $json = json_decode($output, true);
              echo json_encode($json);*/
              $row['Nomor'] = $i++;
              $row['noKunjungan'] = '<a href="'.url([ADMIN, 'vclaim', 'caribynorujukanmodal', $searchBy, $value['noKunjungan']]).'" data-toggle="modal" data-target="#rujukanModal">'.$value['noKunjungan'].'</a>';
              $row['Poliklinik'] = '['.$value['poliRujukan']['kode'].'] '.$value['poliRujukan']['nama'];
              $row['Diagnosa'] = '['.$value['diagnosa']['kode'].'] '.$value['diagnosa']['nama'];
              $row['Perujuk'] = '['.$value['provPerujuk']['kode'].'] '.$value['provPerujuk']['nama'];
              $row['Tanggal'] = $value['tglKunjungan'];
              $noKunjungan[] = $row;
            }
            echo json_encode($noKunjungan);

          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCariByTglRujukan_BackUp($searchBy, $keyword)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        if ($searchBy == 'RS') {
            $url = 'Rujukan/RS/List/TglRujukan/'.$keyword;
        } else {
            $url = 'Rujukan/List/TglRujukan/'.$keyword;
        }
        $url = $this->api_url.''.$url;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo '<pre>'.json_encode($json, JSON_PRETTY_PRINT).'</pre>';
        /*$listRujukan = [];
        $i = 1;
        foreach ($json['response']['rujukan'] as $key=>$value) {
          $row['nomor'] = $i++;
          if ($searchBy == 'RS') {
              $url = 'Rujukan/RS/'.$keyword;
          } else {
              $url = 'Rujukan/'.$keyword;
          }
          $url = $this->api_url.''.$url;
          $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
          $json = json_decode($output, true);
          //echo json_encode($json);
          $row['noRujukan'] = $value['noKunjungan'];
          $listRujukan[] = $row;
        }
        echo json_encode($listRujukan);*/

        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function postInsertLPK($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'LPK/insert';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function postUpdateLPK($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'LPK/update';
        $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function postDeleteLPK($data = [])
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'LPK/delete';
        $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getCariLPK($tglMasuk, $jnsPelayanan)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'LPK/TglMasuk/'.$tglMasuk.'/JnsPelayanan/'.$jnsPelayanan;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDataKunjungan($tglSep, $jnsPelayanan)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Monitoring/Kunjungan/Tanggal/'.$tglSep.'/JnsPelayanan/'.$jnsPelayanan;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDataKlaim($tglPulang, $jnsPelayanan, $statusKlaim)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'Monitoring/Klaim/Tanggal/'.$tglPulang.'/JnsPelayanan/'.$jnsPelayanan.'/Status/'.$statusKlaim;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getHistoriPelayanan($noKartu, $tglAwal, $tglAkhir)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'monitoring/HistoriPelayanan/NoKartu/'.$noKartu.'/tglAwal/'.$tglAwal.'/tglAkhir/'.$tglAkhir;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getDataKlaimJasaRaharja($tglMulai, $tglAkhir)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'monitoring/JasaRaharja/tglMulai/'.$tglMulai.'/tglAkhir/'.$tglAkhir;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    public function getSPRI($no_kartu, $no_rawat)
    {
      $this->_addHeaderFiles();
      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->toArray();
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->toArray();
      $bridging_surat_pri_bpjs = $this->db('bridging_surat_pri_bpjs')->where('no_kartu', $no_kartu)->toArray();
      $this->tpl->set('spri', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_pri_bpjs)));
      $this->tpl->set('maping_dokter_dpjpvclaim', $this->tpl->noParse_array(htmlspecialchars_array($maping_dokter_dpjpvclaim)));
      $this->tpl->set('maping_poli_bpjs', $this->tpl->noParse_array(htmlspecialchars_array($maping_poli_bpjs)));
      $this->tpl->set('no_kartu', $no_kartu);
      $this->tpl->set('no_rawat', revertNorawat($no_rawat));
      echo $this->draw('spri.html');
      exit();
    }

    public function getSPRIDisplay($no_kartu, $no_rawat)
    {
      $bridging_surat_pri_bpjs = $this->db('bridging_surat_pri_bpjs')->where('no_kartu', $no_kartu)->toArray();
      $this->tpl->set('spri', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_pri_bpjs)));
      echo $this->draw('spri.display.html');
      exit();
    }

    public function postSaveSPRI($no_kartu, $no_rawat)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        $_POST['sep_user']	= $this->core->getUserInfo('fullname', null, true);

        $data = [
          'request' => [
              'noKartu' => $no_kartu,
              'kodeDokter' => $_POST['dokter'],
              'poliKontrol' => $_POST['poli'],
              'tglRencanaKontrol' => $_POST['tanggal_periksa'],
              'user' => $_POST['sep_user']
          ]
        ];

        $data = json_encode($data);

        $url = $this->api_url.'RencanaKontrol/InsertSPRI';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $data = json_decode($output, true);
        //echo $data['metaData']['message'];
        if ($data == NULL) {
          echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
        } else if ($data['metaData']['code'] == 200) {
          $stringDecrypt = stringDecrypt($key, $data['response']);
          $decompress = '""';
          $decompress = decompress($stringDecrypt);
          $spri = json_decode($decompress, true);
          //echo $spri['noSPRI'];
          $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $_POST['dokter'])->oneArray();
          $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $_POST['poli'])->oneArray();

          $bridging_surat_pri_bpjs = $this->db('bridging_surat_pri_bpjs')->save([
            'no_rawat' => revertNorawat($no_rawat),
            'no_kartu' => $no_kartu,
            'tgl_surat' => $_POST['tanggal_surat'],
            'no_surat' => $spri['noSPRI'],
            'tgl_rencana' => $_POST['tanggal_periksa'],
            'kd_dokter_bpjs' => $_POST['dokter'],
            'nm_dokter_bpjs' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
            'kd_poli_bpjs' => $_POST['poli'],
            'nm_poli_bpjs' => $maping_poli_bpjs['nm_poli_bpjs'],
            'diagnosa' => '-',
            'no_sep' => '-'
          ]);
        } else {
          echo $data['metaData']['message'];
        }
        exit();
    }

    public function getSync_SEP($no_kartu, $no_rawat)
    {
      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->toArray();
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->toArray();
      $bridging_sep = $this->db('bridging_sep')
        ->where('no_kartu', $no_kartu)
        ->toArray();
      $this->tpl->set('bridging_sep', $this->tpl->noParse_array(htmlspecialchars_array($bridging_sep)));
      $this->tpl->set('maping_dokter_dpjpvclaim', $this->tpl->noParse_array(htmlspecialchars_array($maping_dokter_dpjpvclaim)));
      $this->tpl->set('maping_poli_bpjs', $this->tpl->noParse_array(htmlspecialchars_array($maping_poli_bpjs)));
      $this->tpl->set('no_kartu', $no_kartu);
      $this->tpl->set('no_rawat', $no_rawat);
      echo $this->draw('form.sepvclaim.html');
      exit();
    }

    public function getSyncSepDisplay($no_kartu)
    {
      $bridging_sep = $this->db('bridging_sep')
        ->where('no_kartu', $no_kartu)
        ->toArray();
      $this->tpl->set('bridging_sep', $this->tpl->noParse_array(htmlspecialchars_array($bridging_sep)));
      $this->tpl->set('no_kartu', $no_kartu);
      echo $this->draw('sync_sep.display.html');
      exit();
    }

    public function postSaveSyncSEP()
    {
      $date = date('Y-m-d');
      date_default_timezone_set('UTC');
      $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
      $key = $this->consid.$this->secretkey.$tStamp;

      header('Content-type: text/html');
      $url = $this->settings->get('settings.BpjsApiUrl').'SEP/'.$_POST['no_sep'];
      $consid = $this->settings->get('settings.BpjsConsID');
      $secretkey = $this->settings->get('settings.BpjsSecretKey');
      $userkey = $this->settings->get('settings.BpjsUserKey');
      $output = BpjsService::get($url, NULL, $consid, $secretkey, $userkey, $tStamp);
      $data = json_decode($output, true);
      //print_r($output);
      $code = $data['metaData']['code'];
      $message = $data['metaData']['message'];
      if($this->vclaim_version == 1) {
        //echo json_encode($data);
        $data = $data;
      } else {
        $stringDecrypt = stringDecrypt($key, $data['response']);
        $decompress = '""';
        if(!empty($stringDecrypt)) {
          $decompress = decompress($stringDecrypt);
        }
        if($data != null) {
          $data = '{
            "metaData": {
              "code": "'.$code.'",
              "message": "'.$message.'"
            },
            "response": '.$decompress.'}';
          $data = json_decode($data, true);
        } else {
          $data = '{
            "metaData": {
              "code": "5000",
              "message": "ERROR"
            },
            "response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          $data = json_decode($data, true);
        }
      }

      $url_rujukan = $this->settings->get('settings.BpjsApiUrl').'Rujukan/'.$data['response']['noRujukan'];
      if($_POST['asal_rujukan'] == 2) {
        $url_rujukan = $this->settings->get('settings.BpjsApiUrl').'Rujukan/RS/'.$data['response']['noRujukan'];
      }
      $rujukan = BpjsService::get($url_rujukan, NULL, $consid, $secretkey, $userkey, $tStamp);
      $data_rujukan = json_decode($rujukan, true);
      //print_r($rujukan);

      $code = $data_rujukan['metaData']['code'];
      $message = $data_rujukan['metaData']['message'];
      if($this->vclaim_version == 1) {
        //echo json_encode($data);
        $data_rujukan = $data_rujukan;
      } else {
        $stringDecrypt = stringDecrypt($key, $data_rujukan['response']);
        $decompress = '""';
        if(!empty($stringDecrypt)) {
          $decompress = decompress($stringDecrypt);
        }
        if($data_rujukan != null) {
          $data_rujukan = '{
            "metaData": {
              "code": "'.$code.'",
              "message": "'.$message.'"
            },
            "response": '.$decompress.'}';
          $data_rujukan = json_decode($data_rujukan, true);
        } else {
          $data_rujukan = '{
            "metaData": {
              "code": "5000",
              "message": "ERROR"
            },
            "response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          $data_rujukan = json_decode($data_rujukan, true);
        }
      }

      $no_telp = $data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'];
      if(empty($data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'])){
        $no_telp = '00000000';
      }

      $jenis_pelayanan = '2';
      if($data['response']['jnsPelayanan'] == 'Rawat Inap') {
        $jenis_pelayanan = '1';
      }

      if($data_rujukan['metaData']['code'] == 201) {
        $data_rujukan['response']['rujukan']['tglKunjungan'] = $_POST['tgl_kunjungan'];
        $data_rujukan['response']['rujukan']['provPerujuk']['kode'] = $this->settings->get('settings.ppk_bpjs');
        $data_rujukan['response']['rujukan']['provPerujuk']['nama'] = $this->settings->get('settings.nama_instansi');
        $data_rujukan['response']['rujukan']['diagnosa']['kode'] = $_POST['kd_diagnosa'];
        $data_rujukan['response']['rujukan']['diagnosa']['nama'] = $data['response']['diagnosa'];
        $data_rujukan['response']['rujukan']['pelayanan']['kode'] = $jenis_pelayanan;
      }

      if($data['metaData']['code'] == 200)
      {
        if($data['response']['klsRawat']['klsRawatNaik'] === NULL) {
          $data['response']['klsRawat']['klsRawatNaik'] = '';
        }
        if($data['response']['klsRawat']['pembiayaan'] === NULL) {
          $data['response']['klsRawat']['pembiayaan'] = '';
        }
        if($data['response']['klsRawat']['penanggungJawab'] === NULL) {
          $data['response']['klsRawat']['penanggungJawab'] = '';
        }
        $insert = $this->db('bridging_sep')
          ->save([
            'no_sep' => $data['response']['noSep'],
            'no_rawat' => $_POST['no_rawat'],
            'tglsep' => $data['response']['tglSep'],
            'tglrujukan' => $data_rujukan['response']['rujukan']['tglKunjungan'],
            'no_rujukan' => $data['response']['noRujukan'],
            'kdppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['kode'],
            'nmppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['nama'],
            'kdppkpelayanan' => $this->settings->get('settings.ppk_bpjs'),
            'nmppkpelayanan' => $this->settings->get('settings.nama_instansi'),
            'jnspelayanan' => $jenis_pelayanan,
            'catatan' => $data['response']['catatan'],
            'diagawal' => $data_rujukan['response']['rujukan']['diagnosa']['kode'],
            'nmdiagnosaawal' => $data_rujukan['response']['rujukan']['diagnosa']['nama'],
            'kdpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['kd_poli_bpjs'],
            'nmpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['nm_poli_bpjs'],
            'klsrawat' =>  $data['response']['klsRawat']['klsRawatHak'],
            'klsnaik' => $data['response']['klsRawat']['klsRawatNaik'],
            'pembiayaan' => $data['response']['klsRawat']['pembiayaan'],
            'pjnaikkelas' => $data['response']['klsRawat']['penanggungJawab'],
            'lakalantas' => '0',
            'user' => $this->core->getUserInfo('username', null, true),
            'nomr' => $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']),
            'nama_pasien' => $data['response']['peserta']['nama'],
            'tanggal_lahir' => $data['response']['peserta']['tglLahir'],
            'peserta' => $data['response']['peserta']['jnsPeserta'],
            'jkel' => $data['response']['peserta']['kelamin'],
            'no_kartu' => $data['response']['peserta']['noKartu'],
            'tglpulang' => '1900-01-01 00:00:00',
            'asal_rujukan' => $_POST['asal_rujukan'],
            'eksekutif' => $data['response']['poliEksekutif'],
            'cob' => '0',
            'notelep' => $no_telp,
            'katarak' => '0',
            'tglkkl' => '1900-01-01',
            'keterangankkl' => '-',
            'suplesi' => '0',
            'no_sep_suplesi' => '-',
            'kdprop' => '-',
            'nmprop' => '-',
            'kdkab' => '-',
            'nmkab' => '-',
            'kdkec' => '-',
            'nmkec' => '-',
            'noskdp' => '0',
            'kddpjp' => $_POST['kd_dokter'],
            'nmdpdjp' => $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $_POST['kd_dokter'])->oneArray()['nm_dokter_bpjs'],
            'tujuankunjungan' => '',
            'flagprosedur' => '',
            'penunjang' => '',
            'asesmenpelayanan' => '',
            'kddpjplayanan' => $data['response']['dpjp']['kdDPJP'],
            'nmdpjplayanan' => $data['response']['dpjp']['nmDPJP']
          ]);
      }

      if ($insert) {
          $this->db('bpjs_prb')->save(['no_sep' => $data['response']['noSep'], 'prb' => $data_rujukan['response']['rujukan']['peserta']['informasi']['prolanisPRB']]);
          $this->notify('success', 'Simpan sukes');
      } else {
          $this->notify('failure', 'Simpan gagal');
      }
      exit();
    }

    public function getKontrol($no_kartu)
    {
      $this->_addHeaderFiles();
      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->toArray();
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->toArray();
      $bridging_surat_kontrol_bpjs = $this->db('bridging_surat_kontrol_bpjs')
        ->join('bridging_sep', 'bridging_sep.no_sep=bridging_surat_kontrol_bpjs.no_sep')
        ->where('bridging_sep.no_kartu', $no_kartu)
        ->toArray();
      $this->tpl->set('kontrol', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_kontrol_bpjs)));
      $this->tpl->set('maping_dokter_dpjpvclaim', $this->tpl->noParse_array(htmlspecialchars_array($maping_dokter_dpjpvclaim)));
      $this->tpl->set('maping_poli_bpjs', $this->tpl->noParse_array(htmlspecialchars_array($maping_poli_bpjs)));
      $this->tpl->set('no_kartu', $no_kartu);
      echo $this->draw('kontrol.html');
      exit();
    }

    public function getKontrolDisplay($no_kartu)
    {
      $bridging_surat_kontrol_bpjs = $this->db('bridging_surat_kontrol_bpjs')
        ->join('bridging_sep', 'bridging_sep.no_sep=bridging_surat_kontrol_bpjs.no_sep')
        ->where('bridging_sep.no_kartu', $no_kartu)
        ->toArray();
      $this->tpl->set('kontrol', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_kontrol_bpjs)));
      $this->tpl->set('no_kartu', $no_kartu);
      echo $this->draw('kontrol.display.html');
      exit();
    }

    public function postSaveKontrol()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        $_POST['sep_user']	= $this->core->getUserInfo('fullname', null, true);

        $data = [
          'request' => [
              'noSEP' => $_POST['no_sep'],
              'kodeDokter' => $_POST['dokter'],
              'poliKontrol' => $_POST['poli'],
              'tglRencanaKontrol' => $_POST['tanggal_periksa'],
              'user' => $_POST['sep_user']
          ]
        ];

        $data = json_encode($data);

        $url = $this->api_url.'RencanaKontrol/insert';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $data = json_decode($output, true);
        //echo $data['metaData']['message'];
        if ($data == NULL) {
          echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
        } else if ($data['metaData']['code'] == 200) {
          $stringDecrypt = stringDecrypt($key, $data['response']);
          $decompress = '""';
          $decompress = decompress($stringDecrypt);
          $spri = json_decode($decompress, true);
          //echo $spri['noSuratKontrol'];
          $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $_POST['dokter'])->oneArray();
          $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $_POST['poli'])->oneArray();

          $bridging_surat_pri_bpjs = $this->db('bridging_surat_kontrol_bpjs')->save([
            'no_sep' => $_POST['no_sep'],
            'tgl_surat' => $_POST['tanggal_surat'],
            'no_surat' => $spri['noSuratKontrol'],
            'tgl_rencana' => $_POST['tanggal_periksa'],
            'kd_dokter_bpjs' => $_POST['dokter'],
            'nm_dokter_bpjs' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
            'kd_poli_bpjs' => $_POST['poli'],
            'nm_poli_bpjs' => $maping_poli_bpjs['nm_poli_bpjs']
          ]);

          $query = $this->db('skdp_bpjs')->save([
            'tahun' => date('Y'),
            'no_rkm_medis' => $_POST['no_rkm_medis'],
            'diagnosa' => $_POST['diagnosa'],
            'terapi' => $_POST['terapi'],
            'alasan1' => $_POST['alasan1'],
            'alasan2' => '',
            'rtl1' => $_POST['rtl1'],
            'rtl2' => '',
            'tanggal_datang' => $_POST['tanggal_datang'],
            'tanggal_rujukan' => $_POST['tanggal_rujukan'],
            'no_antrian' => $this->core->setNoSKDP(),
            'kd_dokter' => $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']),
            'status' => 'Menunggu'
          ]);

          if ($query) {
            $this->db('booking_registrasi')
              ->save([
                'tanggal_booking' => date('Y-m-d'),
                'jam_booking' => date('H:i:s'),
                'no_rkm_medis' => $_POST['no_rkm_medis'],
                'tanggal_periksa' => $_POST['tanggal_datang'],
                'kd_dokter' => $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']),
                'kd_poli' => $this->core->getRegPeriksaInfo('kd_poli', $_POST['no_rawat']),
                'no_reg' => $this->core->setNoBooking($this->core->getUserInfo('username', null, true), $_POST['tanggal_datang']),
                'kd_pj' => $this->core->getRegPeriksaInfo('kd_pj', $_POST['no_rawat']),
                'limit_reg' => 0,
                'waktu_kunjungan' => $_POST['tanggal_datang'].' '.date('H:i:s'),
                'status' => 'Belum'
              ]);
          }

        } else {
          echo $data['metaData']['message'];
        }
        exit();
    }

    public function getDataKontrol()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->api_url.'monitoring/JasaRaharja/tglMulai/'.$tglMulai.'/tglAkhir/'.$tglAkhir;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode($json);
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        if($this->vclaim_version == 1) {
          echo json_encode($json);
        } else {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          $decompress = '""';
          if(!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
          }
          if($json != null) {
            echo '{
            	"metaData": {
            		"code": "'.$code.'",
            		"message": "'.$message.'"
            	},
            	"response": '.$decompress.'}';
          } else {
            echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
          }
        }
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
    }

}
