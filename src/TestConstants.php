<?php

namespace Utils;

class TestConstants
{
    public const NAME_MAX_LENGTH = 21;
    public const NAME_CLASSROOM_MAX_LENGTH = 255;
    public const NAME_COLLECTION_MAX_LENGTH = 101;
    public const TEST_STRING = 'test';
    public const TEST_MAIL = 'contact@vittascience.com';
    public const DESCRIPTION_MAX_LENGTH = 1001;
    public const MESSAGE_MAX_LENGTH = 1001;
    public const BIO_MAX_LENGTH = 2001;
    public const TEST_INTEGER = 5;
    public const TEST_CODE = '<xml xmlns="http://www.w3.org/1999/xhtml"><block type="print_printText" id="iG`ds;(537;bnRj~m+w8" x="262" y="12"><value name="TEXT"><shadow type="text" id="E+Yq=/hUmP5XUjK-~n*V"><field name="TEXT">a</field></shadow></value></block></xml>';
    public const TEST_CODE_PYTHON = "for count in range(10):
        print('Bonjour cher utilisateur !')";
    public const TEST_CODE_C = '<xml xmlns="http://www.w3.org/1999/xhtml"><block type="on_start" id=":Lb/qw{tcc5##;NO}}Zr" deletable="false" x="-287" y="-37"></block><block type="forever" id="Ljv{oV6tk1qSu2]LRdpX" deletable="false" x="-37" y="-37"><statement name="DO"><block type="variables_set" id="UUkiaU@qges:eF+;Nh-s"><field name="VAR">i</field><value name="VALUE"><block type="math_number" id="0;9yd%]H9Y9.I%gED1JU"><field name="NUM">15</field></block></value><next><block type="variables_set" id="^LV#bzLU;4U?2Uxev28k"><field name="VAR">j</field><value name="VALUE"><block type="math_number" id="4KIoY-!2QoYsFA!3MPsy"><field name="NUM">0</field></block></value></block></next></block></statement></block></xml>';
    public const TEST_CODE_MIXED = "print('a')";

    private function __construct()
    {
    }
}
