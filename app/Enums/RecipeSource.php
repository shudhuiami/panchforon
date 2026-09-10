<?php

namespace App\Enums;

enum RecipeSource: string
{
    case Api = 'api';
    case User = 'user';
}
