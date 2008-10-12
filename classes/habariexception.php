<?php
class HabariException extends Exception {}

class DBException extends HabariException {}
class PluginException extends HabariException {}
class QueryException extends HabariException {}
class SessionException extends HabariException {}
class ThemeException extends HabariException {}
?>