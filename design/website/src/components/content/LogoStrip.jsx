
import React from 'react';
import { cx } from '../core/LocaleProvider.jsx';

/** Bordered grid of manufacturer logos (or neutral wordmarks when no logo file). brands: {id, name, logo?}[] */
export function LogoStrip({ brands = [], onOpen, className }) {
  return (
    <div className={cx('d-logos', className)}>
      {brands.map((b) => (
        <button key={b.id || b.name} type="button" onClick={() => onOpen && onOpen(b)} aria-label={b.name}>
          {b.logo ? <img src={b.logo} alt={b.name} style={{ maxHeight: 40, maxWidth: '70%', filter: 'grayscale(1)' }} /> : b.name}
        </button>
      ))}
    </div>
  );
}

