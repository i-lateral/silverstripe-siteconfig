<?php

class SiteConfig_Controller extends Extension {

    public function SiteConfig() {
        if(ClassInfo::exists("SiteConfig")) {
            if(method_exists($this->owner->dataRecord, 'getSiteConfig')) {
                return $this->owner->dataRecord->getSiteConfig();
            } else {
                return SiteConfig::current_site_config();
            }
        }
    }

}
