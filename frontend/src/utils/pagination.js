export function pageItems(currentPage, lastPage) {
  const pages = [...new Set([1, currentPage - 1, currentPage, currentPage + 1, lastPage])]
    .filter((page) => page >= 1 && page <= lastPage)
    .sort((a, b) => a - b)

  return pages.flatMap((page, index) => {
    const gap = page - (pages[index - 1] ?? page - 1)

    if (gap === 1) {
      return [page]
    }

    return gap === 2 ? [page - 1, page] : [null, page]
  })
}
