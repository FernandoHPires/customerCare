<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\QuoteBO;
use Illuminate\Http\Request;

class QuoteController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function index(Request $request, $applicationId) {

        $this->logger->info('QuoteController->index',[$applicationId]);

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->index($applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function store(Request $request) {

        $quote = $request->quote;
        $properties = $request->properties;

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->store($quote, $properties);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Quote saved successfully!';
        } else {
            $response->status = 'error';           
            if (count($quoteBO->getErrors()) > 0) {
                $response->message = implode(', ',$quoteBO->getErrors());
            }else {
                $response->message = 'Quote was not saved!';
            }            
        }

        return response()->json($response, 200);
    }

    public function update(Request $request) {

        $quote = $request->quote;

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->update($quote);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Quote saved successfully!';
        } else {
            $response->status = 'error';
            $response->message = 'Quote not saved';
        }

        return response()->json($response, 200);
    }

    public function destroy(Request $request, $savedQuoteId) {

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->destroy($savedQuoteId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = 'Quote deleted successfully!';
        } else {
            $response->status = 'error';
            $response->message = 'Quote could not be deleted!';
        }

        return response()->json($response, 200);
    }

    public function getFees(Request $request, $companyId) {

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->getFees($companyId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getPrimeRate(Request $request) {

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->getPrimeRate();

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getIla(Request $request, $applicationId) {

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->getIla($applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

    public function getCosts(Request $request, $applicationId) {

        $quoteBO = new QuoteBO($this->logger, $this->db);
        $res = $quoteBO->getCosts($applicationId);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = '';
        }

        return response()->json($response, 200);
    }

}