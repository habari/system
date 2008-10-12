<?php
class HabariError extends ErrorException {}

class DBError extends HabariError {}
class PluginError extends HabariError {}
class QueryError extends HabariError {}
class SessionError extends HabariError {}
class ThemeError extends HabariError {}
?>