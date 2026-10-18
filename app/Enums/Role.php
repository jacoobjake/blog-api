<?php

namespace App\Enums;

enum Role: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case AUTHOR = 'author';
}
