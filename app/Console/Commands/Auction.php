<?php

namespace App\Console\Commands;

use App\Auction as AppAuction;
use App\EmailTemplate;
use App\Jobs\SendSingleMail;
use App\System;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Auction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auction:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auction Status Check & Change';

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
     * @return int
     */
    public function handle()
    {
        $this->changeAuctionStatusRunning();
        $this->changeAuctionStatusComplete();
    }

    /**
     * Change Status of Auction as Running
     */
    protected function changeAuctionStatusRunning(){
        $auctions = AppAuction::where('status', '!=', 'running')->where('status', '!=', "cancelled")->where('start_time', '<=', now())->where('end_time', '>=', now())->get();
        foreach($auctions as $auction){
            $auction->status = 'running';
            $auction->save();
        }
    }

    /**
     * Change Status of Auction as Completed
     */
    protected function changeAuctionStatusComplete(){
        $auctions = AppAuction::where('status', '!=', 'completed')->where('status', '!=', "cancelled")->where( 'end_time', '<', now())->get();
        foreach($auctions as $auction){
            $auction->status = 'completed';
            $auction->save();

            $advisor = $auction->max_bidder ?? "";
            if( !empty($advisor) ){
                $email_template = EmailTemplate::where('type', "auction_win_email")->first();
                $subject = $email_template->subject ?? "Auction Win";
                $subject = $this->setDynamicValue($auction, $advisor, $subject);
                $mail_body = $email_template->body ?? "Congratulations! You are winner & You won a Lead";
                $mail_body = $this->setDynamicValue($auction, $advisor, $mail_body);
                $footer = $email_template->footer ?? "";
                $message = ["mail_message" => $mail_body, 'mail_footer' => $footer];
                SendSingleMail::dispatch($advisor, $subject, $message, "email.auction")->delay(1);
            }
        }
        
    }

    /**
     * Set Dynamic Value into Email Template
     */
    protected function setDynamicValue($auction, $advisor, $mail_message){
        $system = System::first();
        $mail_message = str_replace("{PLATFORM_DATE}", Carbon::now()->format($system->date_format), $mail_message);

        $mail_message = str_replace("{ADVISOR_FIRST_NAME}", $advisor->first_name, $mail_message);
        $mail_message = str_replace("{ADVISOR_LAST_NAME}", $advisor->last_name, $mail_message);
        $mail_message = str_replace("{ADVISOR_BILLING_ID}", $advisor->billing_info->id, $mail_message);
        $mail_message = str_replace("{AUCTION_POSTCODE}", $auction->post_code, $mail_message);
        $mail_message = str_replace("{AUCTION_PRIMARY_REGION}", $auction->primary_reason(), $mail_message);
        $mail_message = str_replace("{AUCTION_TYPE}", ucwords($auction->type), $mail_message);
        $mail_message = str_replace("{AUCTION_AREAS_OF_ADVICE}", $auction->service_offered(), $mail_message);
        $mail_message = str_replace("{AUCTION_FUND_SIZE}", $auction->fund_size->name ?? "", $mail_message);
        $mail_message = str_replace("{AUCTION_COMMUNICATION_TYPE}", $auction->communication_type, $mail_message);
        $mail_message = str_replace("{AUCTION_QUESTION}", $auction->question, $mail_message);
        $mail_message = str_replace("{AUCTION_START_DATE}", Carbon::parse($auction->start_time)->format('Y-m-d'), $mail_message);
        $mail_message = str_replace("{AUCTION_START_TIME}", Carbon::parse($auction->start_time)->format('h:i A'), $mail_message);
        $mail_message = str_replace("{AUCTION_END_TIME}", Carbon::parse($auction->end_time)->diff()->format('%H Hour: %I Minute: %S Second'), $mail_message);
        $mail_message = str_replace("{AUCTION_RESERVE_PRICE}", $auction->base_price, $mail_message);     

        return $mail_message;
    }
}
