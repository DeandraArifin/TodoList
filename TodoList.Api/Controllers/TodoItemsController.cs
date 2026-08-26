using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using TodoList.Api.Data;
using TodoList.Api.DTOs;
using TodoList.Api.Models;

namespace TodoList.Api.Controllers;

[ApiController]
[Route("api/todolists/{listId:guid}/items")]
public class TodoItemsController : ControllerBase
{
    private readonly AppDBContext _db;

    public TodoItemsController(AppDBContext db)
    {
        _db = db;
    }

    [HttpGet]
    public async Task<ActionResult<IEnumerable<TodoItemResponse>>> GetAll(
        Guid listId,
        CancellationToken cancellationToken)
    {
        if (!await ListExists(listId, cancellationToken))
        {
            return NotFound();
        }

        var items = await _db.TodoItems
            .AsNoTracking()
            .Where(item => item.TodoListItemId == listId)
            .OrderBy(item => item.Title)
            .ToListAsync(cancellationToken);

        return Ok(items.Select(ToResponse));
    }

    [HttpGet("{id:guid}")]
    public async Task<ActionResult<TodoItemResponse>> GetById(
        Guid listId,
        Guid id,
        CancellationToken cancellationToken)
    {
        var item = await _db.TodoItems
            .AsNoTracking()
            .FirstOrDefaultAsync(todo => todo.Id == id && todo.TodoListItemId == listId, cancellationToken);

        if (item is null)
        {
            return NotFound();
        }

        return Ok(ToResponse(item));
    }

    [HttpPost]
    public async Task<ActionResult<TodoItemResponse>> Create(
        Guid listId,
        CreateTodoItemRequest request,
        CancellationToken cancellationToken)
    {
        if (!await ListExists(listId, cancellationToken))
        {
            return NotFound();
        }

        var item = new TodoItem
        {
            Id = Guid.NewGuid(),
            Title = request.Title,
            IsCompleted = false,
            TodoListItemId = listId
        };

        _db.TodoItems.Add(item);
        await _db.SaveChangesAsync(cancellationToken);

        return CreatedAtAction(nameof(GetById), new { listId, id = item.Id }, ToResponse(item));
    }

    [HttpPut("{id:guid}")]
    public async Task<ActionResult<TodoItemResponse>> Update(
        Guid listId,
        Guid id,
        UpdateTodoItemRequest request,
        CancellationToken cancellationToken)
    {
        var item = await _db.TodoItems
            .FirstOrDefaultAsync(todo => todo.Id == id && todo.TodoListItemId == listId, cancellationToken);

        if (item is null)
        {
            return NotFound();
        }

        item.Title = request.Title;
        item.IsCompleted = request.IsCompleted;
        await _db.SaveChangesAsync(cancellationToken);

        return Ok(ToResponse(item));
    }

    [HttpDelete("{id:guid}")]
    public async Task<IActionResult> Delete(Guid listId, Guid id, CancellationToken cancellationToken)
    {
        var item = await _db.TodoItems
            .FirstOrDefaultAsync(todo => todo.Id == id && todo.TodoListItemId == listId, cancellationToken);

        if (item is null)
        {
            return NotFound();
        }

        _db.TodoItems.Remove(item);
        await _db.SaveChangesAsync(cancellationToken);

        return NoContent();
    }

    private Task<bool> ListExists(Guid listId, CancellationToken cancellationToken) =>
        _db.TodoListItems.AnyAsync(list => list.Id == listId, cancellationToken);

    private static TodoItemResponse ToResponse(TodoItem item) =>
        new(item.Id, item.Title, item.IsCompleted, item.TodoListItemId);
}
