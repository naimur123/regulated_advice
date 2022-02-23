<?php

namespace App\Http\Components\Classes;

use App\AdvisorQuestion;
use App\MatchRating as AppMatchRating;
use App\ServiceOffer;
use App\User;

class MatchRating{

    protected $subscription_plan;
    protected $advisor;
    protected $specific;

    /**
     * @param $plan_id => Subscription Plan
     * @param $advisor => $advisor
     */
    function __construct($advisor = null, $plan_id = null)
    {
        $this->advisor = $advisor;
        if( empty($plan_id) && !empty($advisor) ){
            $this->subscription_plan = $advisor->subscription_plan_id;
        }else{
            $this->subscription_plan = $plan_id;
        }
    }

    /** 
     * Change Match Rating
     */
    public function handel(){
        $advisors = User::orderBy('id', 'ASC');
        if( !empty($this->advisor) ){
            $advisors = $advisors->where('id', $this->advisor->id);
        }
        if( !empty($this->subscription_plan) ){
            $advisors = $advisors->where('subscription_plan_id', $this->subscription_plan);
        }
        $advisors = $advisors->get();
        foreach($advisors as $advisor){
            $rating = 0;
            $star_per_question = $this->getStarPerQuestion($advisor->subscription_plan_id);
            $rating = (count($advisor->approve_advisor_questions) * $star_per_question);
            $advisor->non_specific_rating = $rating >= 5 ? 5 : $rating;

            $advisor->specific_rating = $this->getSpecificRatingArrList($advisor);            
            $advisor->save();
        }
    }

    /**
     * Calculate Star per Question
     */
    protected function getStarPerQuestion($subscription_plan_id, $specific = false, $service_offer_id = null){
        if(! $specific){
            $data = AppMatchRating::where('rating_type', 'non-specific')->where('subscription_plan_id', $subscription_plan_id)->first();
        }else{
            $data = AppMatchRating::where('rating_type', 'specific')->where('service_offer_id', $service_offer_id)->where('subscription_plan_id', $subscription_plan_id)->first();
        }        
        if( empty($data) ){
            return 1;
        }
        return number_format(($data->no_of_star / $data->no_of_question), 2, '.', '');
    }

    /**
     * Calculate Specific Rating
     */
    protected function getSpecificRatingArrList($advisor){
        $rating_arr = [];
        $service_offers = ServiceOffer::all();
        foreach($service_offers as $service_offer){
            $total_qs = AdvisorQuestion::where('advisor_id', $advisor->id)->where('publication_status', true)->where('service_offer_id', $service_offer->id)->count();
            $rating_rate = $this->getStarPerQuestion($advisor->subscription_plan_id, true,  $service_offer->id);
            $rating = $total_qs * $rating_rate;
            $rating_arr[$service_offer->name] = $rating >= 5 ? 5 : $rating;
        }
        return $rating_arr;
    }
}