<?php
class HabariException extends Exception {}

class LocaleException extends HabariException {}
class DBException extends HabariException {}
class PluginException extends HabariException {}
class QueryException extends HabariException {}
class SessionException extends HabariException {}
class ThemeException extends HabariException {}
?>