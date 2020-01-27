<?php

namespace App\Listeners;

class ExchangeRatesListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $value = (string) now();

        setting()->findByKey(ER_SETTING_UPDATE_KEY)->update(compact('value'));
    }
}
