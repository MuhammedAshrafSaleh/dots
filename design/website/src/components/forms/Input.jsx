
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** Labelled text input / textarea with lead icon, trailing slot, help and error text. */
export function Input({ label, help, error, icon, trailing, optional, size, multiline, id, className, style, ...rest }) {
  const { t } = useLocale();
  const auto = React.useId();
  const fid = id || auto;
  const Tag = multiline ? 'textarea' : 'input';
  return (
    <div className={cx('d-field', className)} style={style}>
      {label && <label className="d-label" htmlFor={fid}>{label}{optional && <span className="d-label__opt"> · {t('optional', 'اختياري')}</span>}</label>}
      <div className="d-control">
        {icon && <span className="d-control__lead"><Icon name={icon} size={18} /></span>}
        <Tag id={fid} className={cx('d-input', icon && 'd-input--lead', trailing && 'd-input--trail', size === 'sm' && 'd-input--sm')}
          aria-invalid={error ? 'true' : undefined} {...rest} />
        {trailing && <span className="d-control__trail">{trailing}</span>}
      </div>
      {error ? <span className="d-help d-help--error"><Icon name="circle-alert" size={14} />{error}</span> : help && <span className="d-help">{help}</span>}
    </div>
  );
}

