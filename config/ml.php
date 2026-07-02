<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Topic classification categories
    |--------------------------------------------------------------------------
    |
    | Keyword-based classifier used by App\Services\TopicClassifierService.
    | This is a lightweight stand-in for the scikit-learn based FastAPI
    | classification service described in the design document (section
    | 5.13 / 6.4). It implements the same contract (text in, category +
    | confidence out) so it can be swapped for a real HTTP call to a
    | Python ML microservice later without touching any caller.
    |
    */
    'categories' => [
        'programming' => ['code', 'programming', 'bug', 'function', 'java', 'python', 'php', 'javascript', 'compile', 'syntax', 'algorithm', 'variable', 'class', 'array'],
        'database' => ['database', 'sql', 'query', 'table', 'schema', 'mysql', 'index', 'migration', 'foreign', 'key', 'normalization'],
        'web-development' => ['laravel', 'blade', 'route', 'controller', 'api', 'html', 'css', 'frontend', 'backend', 'framework', 'http', 'rest'],
        'networking' => ['network', 'socket', 'tcp', 'protocol', 'server', 'client', 'websocket', 'connection', 'bandwidth'],
        'mathematics' => ['equation', 'matrix', 'calculus', 'algebra', 'theorem', 'proof', 'derivative', 'integral', 'probability'],
        'project-management' => ['deadline', 'schedule', 'meeting', 'team', 'sprint', 'requirement', 'design', 'documentation', 'presentation'],
        'general' => [],
    ],

    'default_category' => 'general',

];
