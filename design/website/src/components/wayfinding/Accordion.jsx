
import React from 'react';
import { cx, useLocale } from '../core/LocaleProvider.jsx';
import { Icon } from '../core/Icon.jsx';

/** FAQ / details accordion. items: {id, title, content, disabled?}[]. The + turns yellow and rotates to × when open. */
export function Accordion({ items = [], defaultOpen = [], multiple = false, className }) {
  const { tx } = useLocale();
  const [open, setOpen] = React.useState(defaultOpen);
  const toggle = (id) => setOpen((o) => (o.includes(id) ? o.filter((x) => x !== id) : multiple ? [...o, id] : [id]));
  return (
    <div className={cx('d-acc', className)}>
      {items.map((it) => {
        const on = open.includes(it.id);
        return (
          <div key={it.id} className={cx('d-acc__item', on && 'd-acc__item--open')}>
            <button type="button" className="d-acc__btn" aria-expanded={on} disabled={it.disabled} onClick={() => toggle(it.id)}>
              {tx(it.title)}<span className="d-acc__ico"><Icon name="plus" size={16} stroke={2} /></span>
            </button>
            {on && <div className="d-acc__panel">{tx(it.content)}</div>}
          </div>
        );
      })}
    </div>
  );
}

