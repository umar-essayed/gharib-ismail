/**
 * Utility functions for URL mapping, slugification, and string checks
 */

/**
 * Checks if a string is a valid UUID
 */
export function isUuid(str: string): boolean {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(str);
}

/**
 * Standard slugify function to generate SEO-friendly URLs.
 * Handles English and Arabic characters gracefully.
 */
export function slugify(text: string): string {
  if (!text) return 'n-a';
  let slug = text
    .trim()
    .toLowerCase()
    .replace(/[^\p{L}\d]+/gu, '-') // Replace non-alphanumeric/non-letters with hyphens
    .replace(/-+/g, '-')           // Replace multiple hyphens with single hyphen
    .replace(/^-+|-+$/g, '');     // Trim leading/trailing hyphens
  return slug || 'n-a';
}
