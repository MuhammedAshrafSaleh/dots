
import React from 'react';
import { useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';
import { ImageFrame } from '../core/ImageFrame.jsx';
import { QuantityStepper } from '../forms/QuantityStepper.jsx';

/** One cart line: thumb, name, variant summary, stepper + remove, line total. item: {id, name, variant, price, qty, image?} */
export function CartLine({ item, onQty, onRemove }) {
  const { t, tx, price } = useLocale();
  return (
    <div className="d-line">
      <ImageFrame className="d-line__img" src={item.image} contain={!!item.image} />
      <div style={{ minWidth: 0 }}>
        <p className="d-line__name">{tx(item.name)}</p>
        <div className="d-line__var">{tx(item.variant)}</div>
        <div className="d-line__row">
          <QuantityStepper size="sm" value={item.qty} onChange={(q) => onQty && onQty(item, q)} />
          <button type="button" className="d-line__rm" onClick={() => onRemove && onRemove(item)}><Icon name="trash-2" size={14} />{t('Remove', 'حذف')}</button>
        </div>
      </div>
      <div className="d-line__price">{price(item.price * item.qty)}</div>
    </div>
  );
}

