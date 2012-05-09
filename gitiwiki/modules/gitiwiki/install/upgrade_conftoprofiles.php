<?php

class gitiwikiModuleUpgrader_conftoprofiles extends jInstallerModule {

    public $targetVersions = array('0.1a1');
    public $date = '2012-05-06';

    function install() {
        $profiles = new jIniFileModifier(jApp::configPath('profiles.ini.php'));
        $defaultconf = $this->config->getMaster();
        $list = $defaultconf->getSectionList();
        $first = true;
        foreach($list as $section) {
            if (preg_match('/^gwrepo_(.*)$/', $section, $m )) {
                if ($first) {
                    $profiles->setValues(array('default'=>$m[1]), 'gtwrepo');
                    $first = false;
                }

                $values = $defaultconf->getValues($section);
                $profiles->setValues($values, 'gtwrepo:'.$m[1]);
                $defaultconf->removeValue(null, $section);
            }
        }
        $profiles->save();
    }

}
