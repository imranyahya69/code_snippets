<?php

namespace App\Console\Commands;

use App\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPostedOrderTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixpostedordertransactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate worth of sale order and add transaction against customer in leadger for missing transactions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $from = '2021-03-31';
        $to = '2021-12-31';
        $sale_orders = DB::table('sale_orders AS so')
            ->join('invoice AS i', 'i.id', '=', 'so.invoice_id')
            // ->leftJoin('transaction AS t', function ($q) {
            //     $q->on('t.invoice_id', '=', 'so.invoice_id');
            //     $q->where('t.type', '=', 'out');
            // })->whereNull('t.amount')
            ->whereNull('so.deleted_at')
            ->where('so.posted', 1)
            ->whereBetween('so.date', [$from, $to])
            ->select([
                'so.id', 'so.customer_id', 'so.invoice_id', 'so.date',
                'i.shipping', 'i.tax', 'i.discount',  'i.total',
            ])->get();
        $this->progressBar = $this->output->createProgressBar(count($sale_orders));
        $this->progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $this->progressBar->setBarCharacter('#');
        $count = 0;
        foreach ($sale_orders as $order) {
            $transaction = Transaction::where([
                'type' => 'out',
                'invoice_id' => $order->invoice_id,
                'customer_id' => $order->customer_id
            ])->orderBy('id', 'ASC')->first();
            if (empty($transaction)) {
                $count++;
                $transaction = new Transaction;
                $transaction->added_by = 1;
                $transaction->type = "out";
                $transaction->payment_type = 'cash';
                $transaction->amount = $order->total;
                $transaction->date = $order->date;
                $transaction->invoice_id = $order->invoice_id;
                $transaction->customer_id = $order->customer_id;
                $transaction->description = 'auto adjust by system';
                $transaction->save();
                Log::info("ORDER [{$order->id}], CUSTOMER[{$order->customer_id}], INVOICE[{$order->invoice_id}]: updated [{$order->total}]");
            }
            $this->progressBar->advance(1);
        }
        $this->line(PHP_EOL . "{$count} record(s) updated completed successfully!\nfor more information check the log file" . PHP_EOL);
    }
}
