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
    case AUTHORS_VIEW_ANY = 'authors.view.any';
    case AUTHORS_CREATE = 'authors.create';
    case AUTHORS_UPDATE_ANY = 'authors.update.any';
    case AUTHORS_DELETE_ANY = 'authors.delete.any';
    case AUTHORS_VIEW_OWN = 'authors.view.own';
    case AUTHORS_UPDATE_OWN = 'authors.update.own';
}
