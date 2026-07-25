<?php

namespace App\Enums;

enum Permission: string
{
    case BLOGS_CREATE = 'blogs.create';
    case BLOGS_VIEW_ANY = 'blogs.view.any';
    case BLOGS_VIEW_OWN = 'blogs.view.own';
    case BLOGS_UPDATE_ANY = 'blogs.update.any';
    case BLOGS_UPDATE_OWN = 'blogs.update.own';
    case BLOGS_DELETE_ANY = 'blogs.delete.any';
    case BLOGS_DELETE_OWN = 'blogs.delete.own';
}
