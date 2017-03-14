<?php

class Application_Form_LiveStreamingPreferences extends Zend_Form_SubForm
{

    public function init()
    {
        $CC_CONFIG = Config::getConfig();
        $isDemo = isset($CC_CONFIG['demo']) && $CC_CONFIG['demo'] == 1;
        $isStreamConfigable = Application_Model_Preference::GetEnableStreamConf() == "true";

        $defaultFade = Application_Model_Preference::GetDefaultTransitionFade();

        $this->setDecorators(array(
                                 array('ViewScript', array('viewScript' => 'form/preferences_livestream.phtml')),
                             ));

        // automatic trasition on source disconnection
        $auto_transition = new Zend_Form_Element_Checkbox("auto_transition");
        $auto_transition->setLabel(_("Auto Switch Off:"))
                        ->setValue(Application_Model_Preference::GetAutoTransition());
        $this->addElement($auto_transition);

        // automatic switch on upon source connection
        $auto_switch = new Zend_Form_Element_Checkbox("auto_switch");
        $auto_switch->setLabel(_("Auto Switch On:"))
                        ->setValue(Application_Model_Preference::GetAutoSwitch());
        $this->addElement($auto_switch);

        // Default transition fade
        $transition_fade = new Zend_Form_Element_Text("transition_fade");
        $transition_fade->setLabel(_("Switch Transition Fade (s):"))
                        ->setFilters(array('StringTrim'))
                        ->addValidator('regex', false, array('/^\d*(\.\d+)?$/',
                                'messages' => _('Please enter a time in seconds (eg. 0.5)')))
                        ->setValue($defaultFade);
        $this->addElement($transition_fade);

        //Master username
        $master_username = new Zend_Form_Element_Text('master_username');
        $master_username->setAttrib('autocomplete', 'off')
                        ->setAllowEmpty(true)
                        ->setLabel(_('Username:'))
                        ->setFilters(array('StringTrim'))
                        ->setValue(Application_Model_Preference::GetLiveStreamMasterUsername());
        $this->addElement($master_username);

        //Master password
        if ($isDemo) {
                $master_password = new Zend_Form_Element_Text('master_password');
        } else {
                $master_password = new Zend_Form_Element_Password('master_password');
                $master_password->setAttrib('renderPassword','true');
        }
        $master_password->setAttrib('autocomplete', 'off')
                        ->setAttrib('renderPassword','true')
                        ->setAllowEmpty(true)
                        ->setValue(Application_Model_Preference::GetLiveStreamMasterPassword())
                        ->setLabel(_('Password:'))
                        ->setFilters(array('StringTrim'));
        $this->addElement($master_password);

        $masterSourceParams = parse_url(Application_Model_Preference::GetMasterDJSourceConnectionURL());

        // Master source connection url parameters
        $masterSourceHost = new Zend_Form_Element_Text('master_source_host');
        $masterSourceHost->setLabel(_('Host:'))
            ->setAttrib('readonly', true)
            ->setValue(Application_Model_Preference::GetMasterDJSourceConnectionURL());
        $this->addElement($masterSourceHost);

        //liquidsoap harbor.input port
        $betweenValidator = Application_Form_Helper_ValidationTypes::overrideBetweenValidator(1024, 49151);
        $m_port = Application_Model_StreamSetting::getMasterLiveStreamPort();
        $masterSourcePort = new Zend_Form_Element_Text('master_source_port');
        $masterSourcePort->setLabel(_('Master Source Port:'))
            ->setValue($m_port)
            ->setValidators(array($betweenValidator))
            ->addValidator('regex', false, array('pattern'=>'/^[0-9]+$/', 'messages'=>array('regexNotMatch'=>_('Only numbers are allowed.'))));
        $this->addElement($masterSourcePort);

        $m_mount = Application_Model_StreamSetting::getMasterLiveStreamMountPoint();
        $masterSourceMount = new Zend_Form_Element_Text('master_source_mount');
        $masterSourceMount->setLabel(_('Master Source Mount:'))
            ->setValue($m_mount)
            ->setValidators(array(
                array('regex', false, array('/^[^ &<>]+$/', 'messages' => _('Invalid character entered')))));
        $this->addElement($masterSourceMount);

        $showSourceParams = parse_url(Application_Model_Preference::GetLiveDJSourceConnectionURL());

        // Show source connection url parameters
        $showSourceHost = new Zend_Form_Element_Text('show_source_host');
        $showSourceHost->setLabel(_('Host:'))
            ->setAttrib('readonly', true)
            ->setValue(Application_Model_Preference::GetLiveDJSourceConnectionURL());
        $this->addElement($showSourceHost);

        //liquidsoap harbor.input port
        $l_port = Application_Model_StreamSetting::getDjLiveStreamPort();

        $showSourcePort = new Zend_Form_Element_Text('show_source_port');
        $showSourcePort->setLabel(_('Show Source Port:'))
            ->setValue($l_port)
            ->setValidators(array($betweenValidator))
            ->addValidator('regex', false, array('pattern'=>'/^[0-9]+$/', 'messages'=>array('regexNotMatch'=>_('Only numbers are allowed.'))));
        $this->addElement($showSourcePort);

        $l_mount = Application_Model_StreamSetting::getDjLiveStreamMountPoint();
        $showSourceMount = new Zend_Form_Element_Text('show_source_mount');
        $showSourceMount->setLabel(_('Show Source Mount:'))
            ->setValue($l_mount)
            ->setValidators(array(
                array('regex', false, array('/^[^ &<>]+$/', 'messages' => _('Invalid character entered')))));
        $this->addElement($showSourceMount);

        // demo only code
        if ($isDemo) {
            $elements = $this->getElements();
            foreach ($elements as $element) {
                if ($element->getType() != 'Zend_Form_Element_Hidden') {
                    $element->setAttrib("disabled", "disabled");
                }
            }
        }
    }

    public function updateVariables()
    {
        $CC_CONFIG = Config::getConfig();

        $isDemo = isset($CC_CONFIG['demo']) && $CC_CONFIG['demo'] == 1;
        $masterSourceParams = parse_url(Application_Model_Preference::GetMasterDJSourceConnectionURL());
        $showSourceParams = parse_url(Application_Model_Preference::GetLiveDJSourceConnectionURL());

        $this->setDecorators(
            array (
                array ('ViewScript',
                    array (
                      'viewScript'                  => 'form/preferences_livestream.phtml',
                      'master_source_host'          => isset($masterSourceHost)?$masterSourceParams["host"]:"",
                      'master_source_port'          => isset($masterSourcePort)?$masterSourceParams["port"]:"",
                      'master_source_mount'         => isset($masterSourceMount)?$masterSourceParams["path"]:"",
                      'show_source_host'            => isset($showSourceHost)?$showSourceParams["host"]:"",
                      'show_source_port'            => isset($showSourcePort)?$showSourceParams["port"]:"",
                      'show_source_mount'           => isset($showSourceMount)?$showSourceParams["path"]:"",
                      'isDemo'                      => $isDemo,
                    )
                )
            )
        );
    }

    public function isValid($data)
    {
        $isValid = parent::isValid($data);
        $master_source_port = $data['master_source_port'];
        $show_source_port = $data['show_source_port'];

        if ($master_source_port == $show_source_port && $master_source_port != "") {
            $element = $this->getElement('show_source_port');
            $element->addError(_("You cannot use same port as Master DJ port."));
            $isValid = false;
        }
        if ($master_source_port != "") {
            if (is_numeric($master_source_port)) {
                if ($master_source_port != Application_Model_StreamSetting::getMasterLiveStreamPort()) {
                    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                    try {
                        socket_bind($sock, 0, $master_source_port);
                    } catch (Exception $e) {
                        $element = $this->getElement("master_source_port");
                        $element->addError(sprintf(_("Port %s is not available"), $master_source_port));
                        $isValid = false;
                    }

                    socket_close($sock);
                }
            } else {
                $isValid = false;
            }
        }
        if ($show_source_port != "") {
            if (is_numeric($show_source_port)) {
                if ($show_source_port != Application_Model_StreamSetting::getDjLiveStreamPort()) {
                    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                    try {
                        socket_bind($sock, 0, $show_source_port);
                    } catch (Exception $e) {
                        $element = $this->getElement("show_source_port");
                        $element->addError(sprintf(_("Port %s is not available"), $show_source_port));
                        $isValid = false;
                    }
                    socket_close($sock);
                }
            } else {
                $isValid = false;
            }
        }

        return $isValid;

    }

}
