<?php

namespace App\Enums;

/** Where a WhatsApp quote request originated. */
enum QuoteSource: string
{
    case Product = 'product';
    case Cart = 'cart';
    case InstapayOrder = 'instapay_order';
    case Boq = 'boq';
    case Brand = 'brand';
    case BrandDetail = 'brand_detail';
    case Service = 'service';
    case Services = 'services';
    case ServiceDetail = 'service_detail';
    case Project = 'project';
    case ProjectDetail = 'project_detail';
    case ProjectShare = 'project_share';
    case ProjectsCurrent = 'projects_current';
    case Article = 'article';
    case OrderHelp = 'order_help';
    case AboutCta = 'about_cta';
    case Consultation = 'consultation';
    case ContentHelp = 'content_help';
    case General = 'general';
}
