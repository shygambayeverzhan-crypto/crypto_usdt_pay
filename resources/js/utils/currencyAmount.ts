export type CurrencyAmountRule = {
  decimals: number;
  example?: string;
  decimal_separator?: string;
  min?: string | null;
  max?: string | null;
};

/**
 * Санитизирует ввод суммы:
 * - разрешены только цифры и одна точка
 * - запятая превращается в точку
 * - дробная часть ограничивается `decimals`
 *
 * Возвращает строку, пригодную для отправки на бэк.
 */
export function sanitizeCurrencyAmountInput(raw: string, decimals: number): string {
  let v = (raw ?? '').toString();

  // Приводим "человеческий" ввод к единому виду
  v = v.replace(/,/g, '.');

  // Убираем всё кроме цифр и точки
  v = v.replace(/[^\d.]/g, '');

  // Если дробная часть не разрешена — просто берём целую часть
  if (decimals <= 0) {
    return v.split('.')[0] ?? '';
  }

  // Оставляем только одну точку (первую)
  const dot = v.indexOf('.');
  if (dot !== -1) {
    const head = v.slice(0, dot + 1);
    const tail = v
      .slice(dot + 1)
      .replace(/\./g, '')
      .slice(0, decimals);
    v = head + tail;
  }

  // Нормализуем лидирующие нули в целой части: "00012.3" -> "12.3", "000" -> "0"
  const parts = v.split('.');
  const intPart = parts[0] ?? '';
  const fracPart = parts[1];
  let intNormalized = intPart.replace(/^0+(?=\d)/, '');
  if (intNormalized === '') intNormalized = intPart === '' ? '' : '0';

  return fracPart !== undefined ? `${intNormalized}.${fracPart}` : intNormalized;
}

/**
 * Небольшая нормализация "по завершению ввода":
 * - убирает конечную точку ("12." -> "12")
 */
export function normalizeCurrencyAmountOnBlur(value: string): string {
  const v = (value ?? '').toString().trim();
  if (v.endsWith('.')) return v.slice(0, -1);
  return v;
}


