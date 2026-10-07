
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';

/** EN / ع segmented language switch. Switching also flips document direction via LocaleProvider. */
export function LangSwitch({ value, onChange }) {
  const { lang, t } = useLocale();
  const cur = value || lang;
  return (
    <div className="d-lang" role="group" aria-label={t('Language', 'اللغة')}>
      <button type="button" lang="en" aria-pressed={cur === 'en'} onClick={() => onChange && onChange('en')}>EN</button>
      <button type="button" lang="ar" aria-pressed={cur === 'ar'} onClick={() => onChange && onChange('ar')}>عربي</button>
    </div>
  );
}

