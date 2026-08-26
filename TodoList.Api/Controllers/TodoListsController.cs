using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using TodoList.Api.Data;
using TodoList.Api.DTOs;
using TodoList.Api.Models;

namespace TodoList.Api.Controllers;

[ApiController]
[Route("api/todolists")]
public class TodoListsController : ControllerBase
{
    private readonly AppDBContext _db;

    public TodoListsController(AppDBContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<ActionResult<IEnumerable<TodoListResponse>>> GetAll(CancellationToken cancellationToken)
    {
        var lists = await _db.TodoListItems
            .AsNoTracking()
            .Include(list => list.Todos)
            .OrderBy(list => list.Title)
            .ToListAsync(cancellationToken);

        return Ok(lists.Select(ToResponse));
    }

    [HttpGet("{id:guid}")]
    public async Task<ActionResult<TodoListResponse>> GetById(Guid id, CancellationToken cancellationToken)
    {
        var list = await _db.TodoListItems
            .AsNoTracking()
            .Include(item => item.Todos)
            .FirstOrDefaultAsync(item => item.Id == id, cancellationToken);

        if (list is null)
        {
            return NotFound();
        }

        return Ok(ToResponse(list));
    }

    [HttpPost]
    public async Task<ActionResult<TodoListResponse>> Create(
        CreateTodoListRequest request,
        CancellationToken cancellationToken)
    {
        var list = new TodoListItem
        {
            Id = Guid.NewGuid(),
            Title = request.Title
        };

        _db.TodoListItems.Add(list);
        await _db.SaveChangesAsync(cancellationToken);

        return CreatedAtAction(nameof(GetById), new { id = list.Id }, ToResponse(list));
    }

    [HttpPut("{id:guid}")]
    public async Task<ActionResult<TodoListResponse>> Update(
        Guid id,
        UpdateTodoListRequest request,
        CancellationToken cancellationToken)
    {
        var list = await _db.TodoListItems
            .Include(item => item.Todos)
            .FirstOrDefaultAsync(item => item.Id == id, cancellationToken);

        if (list is null)
        {
            return NotFound();
        }

        list.Title = request.Title;
        await _db.SaveChangesAsync(cancellationToken);

        return Ok(ToResponse(list));
    }

    [HttpDelete("{id:guid}")]
    public async Task<IActionResult> Delete(Guid id, CancellationToken cancellationToken)
    {
        var list = await _db.TodoListItems.FindAsync([id], cancellationToken);
        if (list is null)
        {
            return NotFound();
        }

        _db.TodoListItems.Remove(list);
        await _db.SaveChangesAsync(cancellationToken);

        return NoContent();
    }

    private static TodoListResponse ToResponse(TodoListItem list) =>
        new(
            list.Id,
            list.Title,
            list.Todos.Select(item => new TodoItemResponse(item.Id, item.Title, item.IsCompleted, item.TodoListItemId)).ToList());
}
