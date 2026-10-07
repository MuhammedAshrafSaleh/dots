
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** variant: primary (yellow) | dark | outline | ghost | whatsapp (Request a Quote) | quote-outline */
export function Button({ variant = 'primary', size = 'md', icon, iconEnd, block, children, className, as = 'button', type, ...rest }) {
  const El = as;
  const isz = size === 'sm' ? 16 : size === 'lg' ? 20 : 18;
  const lead = icon ?? (variant === 'whatsapp' || variant === 'quote-outline' ? 'message-circle' : null);
  return (
    <El
      className={cx('d-btn', `d-btn--${variant}`, size !== 'md' && `d-btn--${size}`, block && 'd-btn--block', !children && 'd-btn--icon', className)}
      type={El === 'button' ? type || 'button' : undefined}
      {...rest}
    >
      {lead && <Icon name={lead} size={isz} />}
      {children}
      {iconEnd && <Icon name={iconEnd} size={isz} />}
    </El>
  );
}

