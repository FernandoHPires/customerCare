<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\Storage;

class PapMigrationController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function reconcilePayments(Request $request) {
        $cwbPayments = $this->getCwbPayments();
        $taclPayments = $this->getTaclPayments();

        $reconciled = array();

        foreach($cwbPayments as $cwbPaymentKey => $cwbPayment) {
            foreach($taclPayments as $taclPaymentKey => $taclPayment) {
                if(
                    $cwbPayments[$cwbPaymentKey]['mortgageId'] == $taclPayments[$taclPaymentKey]['mortgageId'] &&
                    $cwbPayments[$cwbPaymentKey]['date'] == $taclPayments[$taclPaymentKey]['date'] &&
                    $cwbPayments[$cwbPaymentKey]['amount'] == $taclPayments[$taclPaymentKey]['amount']
                ) {
                    $reconciled[] = [
                        'cwb' => $cwbPayments[$cwbPaymentKey],
                        'tacl' => $taclPayments[$taclPaymentKey]
                    ];
                    unset($cwbPayments[$cwbPaymentKey]);
                    unset($taclPayments[$taclPaymentKey]);
                    break;
                }
            }
        }

        foreach($taclPayments as $taclPaymentKey => $taclPayment) {
            foreach($cwbPayments as $cwbPaymentKey => $cwbPayment) {
                if(
                    $cwbPayments[$cwbPaymentKey]['mortgageId'] == $taclPayments[$taclPaymentKey]['mortgageId'] &&
                    $cwbPayments[$cwbPaymentKey]['date'] == $taclPayments[$taclPaymentKey]['date'] &&
                    $cwbPayments[$cwbPaymentKey]['amount'] == $taclPayments[$taclPaymentKey]['amount']
                ) {
                    $reconciled[] = [
                        'cwb' => $cwbPayments[$cwbPaymentKey],
                        'tacl' => $taclPayments[$taclPaymentKey]
                    ];
                    unset($cwbPayments[$cwbPaymentKey]);
                    unset($taclPayments[$taclPaymentKey]);
                    break;
                }
            }
        }

        $response = [
            'reconciled' => $reconciled,
            'tacl' => array_values($taclPayments),
            'cwb' => array_values($cwbPayments)
        ];
        return response()->json($response, 200);
    }

    public function getTaclPayments() {
        $sql = "select b.payment_id, a.mortgage_id, a.mortgage_code, b.processing_date, b.pmt_amt amount
                from mortgage_table a
                join mortgage_payments_table b on a.mortgage_id = b.mortgage_id
                where company_id in (5,183)
                and a.current_balance > 0
                and b.pap_file_payment_id is null
                and (b.flag in ('Pre','Pre2','Pre3','Pre4') or b.is_bank_payment = 'yes')
                and b.processing_date <= date_add(now(), interval 35 day)";
        $res = $this->db->select($sql);

        $payments = array();
        foreach($res as $key => $value) {
            $payments[] = [
                'id' => $value->mortgage_id . '-' . $value->payment_id,
                'mortgageId' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'amount' => (float) $value->amount,
                'date' => $value->processing_date
            ];
        }

        return $payments;
    }

    public function getCwbPayments() {
        $sql = "select * from tmp_pap_cwb where due_date >= date(now()) and substr(mortgage_code,1,3) in ('RMC','RQC')";
        $res = $this->db->select($sql);

        $payments = array();
        foreach($res as $key => $value) {
            $payments[] = [
                'id' => $value->id,
                'mortgageId' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'amount' => (float) $value->amount,
                'date' => $value->due_date
            ];
        }

        return $payments;
    }

    //----------------------
    public function loadCwb(Request $request) {
        //$path = Storage::disk('local')->path('tmp/mii.csv');
        $path = Storage::disk('local')->path('tmp/rmif.csv');

        if(($handle = fopen($path, 'r')) !== FALSE) {
            while(($data = fgetcsv($handle, 10000, ',')) !== FALSE) {
                $res = explode(' ',$data[67]);
                $mortgageCode = $res[1] ?? null;

                $fields = [
                    'id' => null,
                    'cross_ref' => $data[67],
                    'mortgage_id' => null,
                    'mortgage_code' => $mortgageCode,
                    'institution' => $data[69],
                    'institution_id' => null,
                    'transit' => $data[70],
                    'account' => $data[71],
                    'payee_name' => $data[66],
                    'amount' => str_replace(',','',$data[72]),
                    'type' => $data[73],
                    'type_code' => $data[74],
                    'due_date' => empty($data[75]) ? null : DateTime::createFromFormat('Y-M-d', $data[75]),
                    'frequency' => $data[76],
                    'expiry_date' => empty($data[77]) ? null : DateTime::createFromFormat('Y-M-d', $data[77]),
                    'status' => null
                ];

                $this->db->insert('tmp_pap_cwb2', $fields);

                /*$fields = ['payee_name' => trim($data[66])];
                $conditions = ['transit' => $data[70], 'account' => $data[71]];
                $this->db->update('pap_bank', $fields, $conditions);*/
            }
        }

        return response()->json([], 200);
    }

    public function loadOtherData() {
        $sql = "select a.id, a.mortgage_code, b.mortgage_id, c.id institution_id
                from tmp_pap_cwb2 a
                left join mortgage_table b on a.mortgage_code = b.mortgage_code
                left join pap_institution c on c.code = lpad(a.institution, 3, '0')";
        $res = $this->db->select($sql);

        foreach($res as $key => $value) {
            $fields = ['mortgage_id' => $value->mortgage_id, 'institution_id' => $value->institution_id];
            $conditions = ['id' => $value->id];
            $this->db->update('tmp_pap_cwb2', $fields, $conditions);
        }

        return response()->json([], 200);
    }
    
}